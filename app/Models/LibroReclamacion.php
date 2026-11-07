<?php

namespace App\Models;

use App\Support\BusinessDays;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class LibroReclamacion extends Model
{
    public const STATUS_PENDING = 'pendiente';

    public const STATUS_REVIEWING = 'en_revision';

    public const STATUS_RESOLVED = 'atendido';

    public const STATUSES = [self::STATUS_PENDING, self::STATUS_REVIEWING, self::STATUS_RESOLVED];

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Pendiente',
        self::STATUS_REVIEWING => 'En revisión',
        self::STATUS_RESOLVED => 'Atendido',
    ];

    /** Plazo legal para responder un reclamo en el libro de reclamaciones peruano. */
    private const LEGAL_DEADLINE_BUSINESS_DAYS = 15;

    /** A partir de cuántos días hábiles restantes se considera "por vencer". */
    private const DUE_SOON_BUSINESS_DAYS = 3;

    protected $table = 'libro_reclamaciones';

    protected $fillable = [
        'consumer_name',
        'document_type',
        'document_number',
        'address',
        'email',
        'phone',
        'is_minor',
        'guardian_name',
        'guardian_document_type',
        'guardian_document_number',
        'receipt_type',
        'order_number',
        'purchase_date',
        'purchase_channel',
        'claimed_amount',
        'order_product',
        'order_description',
        'claim_type',
        'expected_solution',
        'claim_product',
        'claim_description',
        'status',
        'responded_at',
    ];

    protected $casts = [
        'is_minor' => 'boolean',
        'purchase_date' => 'date',
        'claimed_amount' => 'decimal:2',
        'responded_at' => 'datetime',
    ];

    public function legalDeadline(): Carbon
    {
        return BusinessDays::addDays($this->created_at, self::LEGAL_DEADLINE_BUSINESS_DAYS);
    }

    public function isAnswered(): bool
    {
        return $this->responded_at !== null;
    }

    public function isOverdue(): bool
    {
        return ! $this->isAnswered() && now()->greaterThan($this->legalDeadline());
    }

    public function isDueSoon(): bool
    {
        if ($this->isAnswered() || $this->isOverdue()) {
            return false;
        }

        return BusinessDays::countBetween(now(), $this->legalDeadline()) <= self::DUE_SOON_BUSINESS_DAYS;
    }

    public function businessDaysOverdue(): int
    {
        return BusinessDays::countBetween($this->legalDeadline(), now());
    }

    public function businessDaysUntilDeadline(): int
    {
        return BusinessDays::countBetween(now(), $this->legalDeadline());
    }

    public function wasAnsweredOnTime(): bool
    {
        return $this->isAnswered() && $this->responded_at->lessThanOrEqualTo($this->legalDeadline());
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function maskedDocument(): string
    {
        $digits = (string) $this->document_number;
        $last = mb_substr($digits, -4);

        return trim($this->document_type.' ••••'.$last);
    }

    public function maskedEmail(): string
    {
        [$local, $domain] = array_pad(explode('@', (string) $this->email, 2), 2, '');

        return mb_substr($local, 0, 2).'•••@'.$domain;
    }
}
