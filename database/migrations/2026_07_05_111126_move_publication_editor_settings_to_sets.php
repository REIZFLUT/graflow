<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('publications', function (Blueprint $table) {
            $table->foreignId('editor_settings_set_id')
                ->nullable()
                ->after('name')
                ->constrained('editor_settings_sets')
                ->nullOnDelete();
        });

        if (
            Schema::hasColumn('publications', 'editor_font')
            && Schema::hasColumn('publications', 'editor_has_marginal_column')
        ) {
            $this->migrateExistingPublicationSettings();
        }

        Schema::table('publications', function (Blueprint $table) {
            if (Schema::hasColumn('publications', 'editor_font')) {
                $table->dropColumn('editor_font');
            }

            if (Schema::hasColumn('publications', 'editor_has_marginal_column')) {
                $table->dropColumn('editor_has_marginal_column');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('publications', function (Blueprint $table) {
            $table->string('editor_font')->default('spectral')->after('name');
            $table->boolean('editor_has_marginal_column')->default(true)->after('editor_font');
        });

        $publications = DB::table('publications')
            ->whereNotNull('editor_settings_set_id')
            ->get();

        foreach ($publications as $publication) {
            $set = DB::table('editor_settings_sets')
                ->where('id', $publication->editor_settings_set_id)
                ->first();

            if ($set === null) {
                continue;
            }

            DB::table('publications')
                ->where('id', $publication->id)
                ->update([
                    'editor_font' => $set->font,
                    'editor_has_marginal_column' => $set->has_marginal_column,
                ]);
        }

        Schema::table('publications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('editor_settings_set_id');
        });
    }

    private function migrateExistingPublicationSettings(): void
    {
        /** @var array<string, int> $setIdsByKey */
        $setIdsByKey = [];

        $publications = DB::table('publications')->get();

        foreach ($publications as $publication) {
            $key = implode('|', [
                $publication->owner_id,
                $publication->editor_font,
                (int) $publication->editor_has_marginal_column,
            ]);

            if (! isset($setIdsByKey[$key])) {
                $setIdsByKey[$key] = DB::table('editor_settings_sets')->insertGetId([
                    'name' => $this->uniqueSetName(
                        (int) $publication->owner_id,
                        $this->defaultSetName(
                            $publication->editor_font,
                            (bool) $publication->editor_has_marginal_column,
                        ),
                    ),
                    'font' => $publication->editor_font,
                    'has_marginal_column' => $publication->editor_has_marginal_column,
                    'owner_id' => $publication->owner_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('publications')
                ->where('id', $publication->id)
                ->update([
                    'editor_settings_set_id' => $setIdsByKey[$key],
                ]);
        }
    }

    private function defaultSetName(string $font, bool $hasMarginalColumn): string
    {
        $fontLabel = $font === 'roboto' ? 'Roboto' : 'Spectral';

        return $hasMarginalColumn
            ? "{$fontLabel} mit Marginalspalte"
            : "{$fontLabel} ohne Marginalspalte";
    }

    private function uniqueSetName(int $ownerId, string $baseName): string
    {
        $name = $baseName;
        $suffix = 2;

        while (
            DB::table('editor_settings_sets')
                ->where('owner_id', $ownerId)
                ->where('name', $name)
                ->exists()
        ) {
            $name = "{$baseName} ({$suffix})";
            $suffix++;
        }

        return $name;
    }
};
