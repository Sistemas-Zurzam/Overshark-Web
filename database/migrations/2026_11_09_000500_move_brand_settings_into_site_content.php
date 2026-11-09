<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * «Identidad visual» desaparece del panel: el WhatsApp pasa a Contenido de la tienda y el logo y el ícono a Banners
 * (imágenes fijas). Lo que ya estaba guardado en brand_settings se copia a page_contents («tienda») antes de borrar
 * la tabla, para no perder el número ni las imágenes subidas (los archivos se quedan donde están).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('brand_settings')) {
            $row = DB::table('brand_settings')->first();

            if ($row) {
                $page = DB::table('page_contents')->where('slug', 'tienda')->first();
                $content = $page ? (json_decode((string) $page->content, true) ?: []) : [];

                foreach (['site_logo' => $row->logo_path ?? null, 'site_icon' => $row->icon_path ?? null] as $key => $path) {
                    if ($path && empty($content['images'][$key])) {
                        $content['images'][$key] = $path;
                    }
                }

                foreach (['whatsapp' => $row->whatsapp_number ?? null, 'whatsapp_message' => $row->whatsapp_message ?? null] as $key => $value) {
                    if (filled($value) && empty($content['contacto'][$key])) {
                        $content['contacto'][$key] = $value;
                    }
                }

                if ($content !== []) {
                    $json = json_encode($content, JSON_UNESCAPED_UNICODE);

                    $page
                        ? DB::table('page_contents')->where('slug', 'tienda')->update(['content' => $json, 'updated_at' => now()])
                        : DB::table('page_contents')->insert(['slug' => 'tienda', 'content' => $json, 'created_at' => now(), 'updated_at' => now()]);

                    Cache::forget('page_content.tienda');
                }
            }

            Schema::drop('brand_settings');
        }
    }

    public function down(): void
    {
        Schema::create('brand_settings', function (Blueprint $table) {
            $table->id();
            $table->string('logo_path')->nullable();
            $table->string('icon_path')->nullable();
            $table->string('whatsapp_number', 20)->nullable();
            $table->string('whatsapp_message', 300)->nullable();
            $table->timestamps();
        });
    }
};
