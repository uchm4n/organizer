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
        Schema::create('download_logs', function (Blueprint $table) {
            $table->id();
            $table->string('filename', 255);
            $table->string('ip', 45);
            $table->string('user_agent', 512);
            $table->string('referer', 512)->nullable();
            $table->json('extra')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('filename');
        });

        DB::statement('CREATE VIEW download_stats AS
            SELECT
                dl1.filename,
                COUNT(*) AS download_count,
                COUNT(DISTINCT dl1.ip) AS unique_ips,
                MIN(dl1.created_at) AS first_downloaded_at,
                MAX(dl1.created_at) AS last_downloaded_at,
                (
                    SELECT dl2.referer
                    FROM download_logs dl2
                    WHERE dl2.filename = dl1.filename
                    ORDER BY dl2.created_at DESC
                    LIMIT 1
                ) AS last_referer
            FROM download_logs dl1
            GROUP BY dl1.filename
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS download_stats');
        Schema::dropIfExists('download_logs');
    }
};
