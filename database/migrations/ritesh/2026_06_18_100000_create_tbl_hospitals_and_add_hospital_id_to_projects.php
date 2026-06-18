<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/** Hospitals master for project assignment. No DB foreign keys. */
class CreateTblHospitalsAndAddHospitalIdToProjects extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('tbl_hospitals')) {
            Schema::create('tbl_hospitals', function (Blueprint $table) {
                $table->id();
                $table->string('hospital_code', 50)->unique();
                $table->string('hospital_name', 255);
                $table->text('description')->nullable();
                $table->integer('status')->default(1);
                $table->integer('created_by')->nullable();
                $table->dateTime('created_on')->nullable();
                $table->integer('updated_by')->nullable();
                $table->dateTime('updated_on')->nullable();
                $table->tinyInteger('is_delete')->default(0);

                $table->index('hospital_name');
                $table->index('status');
                $table->index('is_delete');
            });
        }

        if (Schema::hasTable('tbl_projects') && !Schema::hasColumn('tbl_projects', 'hospital_id')) {
            Schema::table('tbl_projects', function (Blueprint $table) {
                $table->integer('hospital_id')->nullable()->after('hospital_name')
                    ->comment('tbl_hospitals.id');
                $table->index('hospital_id');
            });
        }

        $this->backfillHospitalsFromProjects();
    }

    public function down()
    {
        if (Schema::hasTable('tbl_projects') && Schema::hasColumn('tbl_projects', 'hospital_id')) {
            Schema::table('tbl_projects', function (Blueprint $table) {
                $table->dropIndex(['hospital_id']);
                $table->dropColumn('hospital_id');
            });
        }

        Schema::dropIfExists('tbl_hospitals');
    }

    private function backfillHospitalsFromProjects(): void
    {
        if (!Schema::hasTable('tbl_hospitals') || !Schema::hasTable('tbl_projects')) {
            return;
        }

        $now = now();
        $names = DB::table('tbl_projects')
            ->where('is_delete', 0)
            ->whereNotNull('hospital_name')
            ->where('hospital_name', '!=', '')
            ->distinct()
            ->orderBy('hospital_name')
            ->pluck('hospital_name');

        foreach ($names as $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }

            $existing = DB::table('tbl_hospitals')
                ->where('is_delete', 0)
                ->where('hospital_name', $name)
                ->first();

            if (!$existing) {
                $code = $this->uniqueHospitalCode($name);
                $hospitalId = DB::table('tbl_hospitals')->insertGetId([
                    'hospital_code' => $code,
                    'hospital_name' => $name,
                    'description' => null,
                    'status' => 1,
                    'created_by' => 1,
                    'created_on' => $now,
                    'updated_by' => 1,
                    'updated_on' => $now,
                    'is_delete' => 0,
                ]);
            } else {
                $hospitalId = (int) $existing->id;
            }

            DB::table('tbl_projects')
                ->where('is_delete', 0)
                ->where('hospital_name', $name)
                ->where(function ($query) {
                    $query->whereNull('hospital_id')->orWhere('hospital_id', 0);
                })
                ->update(['hospital_id' => $hospitalId]);
        }
    }

    private function uniqueHospitalCode(string $name): string
    {
        $base = Str::slug($name, '_');
        if ($base === '') {
            $base = 'hospital';
        }

        $code = substr($base, 0, 45);
        $suffix = 1;
        while (DB::table('tbl_hospitals')->where('hospital_code', $code)->exists()) {
            $code = substr($base, 0, 40) . '_' . $suffix;
            $suffix++;
        }

        return $code;
    }
}
