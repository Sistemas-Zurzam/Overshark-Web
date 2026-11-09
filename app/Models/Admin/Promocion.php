<?php

namespace App\Models\Admin;

use App\Support\GirlsHome;
use App\Support\Promotions;
use App\Support\StorefrontCategories;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Promoción por horas (panel → Promociones). Se evalúa con la hora de cada petición: al vencer, el precio vuelve
 * solo al normal; no hay proceso programado que lo haga.
 */
class Promocion extends Model
{
    public const SCOPE_PRODUCT = 'producto';

    public const SCOPE_BRAND = 'marca';

    public const SCOPE_CATEGORY = 'categoria';

    public const DISCOUNT_PERCENT = 'porcentaje';

    public const DISCOUNT_FIXED = 'precio_fijo';

    /** Hora en la que se escriben y se muestran las fechas del panel (Perú no tiene horario de verano). */
    public const TIMEZONE = 'America/Lima';

    public const STATUS_LABELS = [
        'activa' => 'Activa',
        'programada' => 'Programada',
        'finalizada' => 'Finalizada',
        'inactiva' => 'Pausada',
    ];

    protected $table = 'promociones';

    protected $fillable = ['name', 'label', 'scope_type', 'scope_values', 'discount_type', 'discount_value', 'starts_at', 'ends_at', 'active'];

    protected static function booted(): void
    {
        static::saved(fn () => Promotions::forget());
        static::deleted(fn () => Promotions::forget());
    }

    protected function casts(): array
    {
        return [
            'scope_values' => 'array',
            'discount_value' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'active' => 'boolean',
        ];
    }

    /** Las que valen en este instante. */
    public function scopeLive(Builder $query): Builder
    {
        return $query->where('active', true)->where('starts_at', '<=', now())->where('ends_at', '>', now());
    }

    public function status(): string
    {
        return match (true) {
            ! $this->active => 'inactiva',
            $this->ends_at->lte(now()) => 'finalizada',
            $this->starts_at->gt(now()) => 'programada',
            default => 'activa',
        };
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status()];
    }

    public function isLive(): bool
    {
        return $this->status() === 'activa';
    }

    /** Fecha y hora en hora de Lima, para el panel. */
    public function local(string $attribute): Carbon
    {
        return $this->{$attribute}->copy()->setTimezone(self::TIMEZONE);
    }

    /** Interpreta lo escrito en el panel (hora de Lima) y lo pasa a UTC para guardarlo. */
    public static function parseLocal(string $value): Carbon
    {
        return Carbon::parse($value, self::TIMEZONE)->utc();
    }

    /** ¿La prenda entra en esta promoción? Marca como en Producto::scopeOfBrand (marca asignada o, si no, empresa). */
    public function appliesTo(string $name, ?string $marca, ?string $empresa): bool
    {
        $values = collect($this->scope_values)->map(fn ($value) => mb_strtoupper(trim((string) $value)));

        return match ($this->scope_type) {
            self::SCOPE_PRODUCT => $values->contains(mb_strtoupper(trim($name))),
            self::SCOPE_BRAND => $values->contains(mb_strtoupper(trim((string) (filled($marca) ? $marca : $empresa)))),
            self::SCOPE_CATEGORY => $values->contains(mb_strtoupper(self::categoryKey($name, $marca))),
            default => false,
        };
    }

    /** Categoría de la tienda de una prenda como «línea:categoría» (p. ej. «hombre:polos»); vacío si no encaja en ninguna. */
    public static function categoryKey(string $name, ?string $marca): string
    {
        $line = GirlsHome::isGirls($name, $marca) ? 'mujer' : 'hombre';
        $slug = StorefrontCategories::slugFor($name, null, $line);

        return $slug ? $line.':'.$slug : '';
    }

    /** Precio con la promoción sobre un precio regular. Nunca sube el precio ni baja de S/ 0.10. */
    public function priceFor(float $regular): float
    {
        $discounted = $this->discount_type === self::DISCOUNT_PERCENT
            ? $regular * (1 - (float) $this->discount_value / 100)
            : (float) $this->discount_value;

        $discounted = round($discounted, 2);

        return $discounted >= $regular || $discounted < 0.10 ? $regular : $discounted;
    }

    public function discountLabel(): string
    {
        return $this->discount_type === self::DISCOUNT_PERCENT
            ? rtrim(rtrim(number_format((float) $this->discount_value, 2), '0'), '.').'% de descuento'
            : 'Precio fijo S/ '.number_format((float) $this->discount_value, 2);
    }
}
