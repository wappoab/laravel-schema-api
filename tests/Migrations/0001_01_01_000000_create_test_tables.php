<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignUuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });

        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('status');
            $table->text('content')->nullable();
            $table->foreignUuid('author_id')
                ->nullable()
                ->references('id')
                ->on('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->timestampsTz();
            $table->softDeletesTz();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->foreignUuid('author_id')
                ->nullable()
                ->references('id')
                ->on('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->timestampsTz();
            $table->softDeletesTz();
        });

        Schema::create('category_posts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('category_id')
                ->references('id')
                ->on('categories')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignUuid('post_id')
                ->references('id')
                ->on('posts')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->unique(['category_id', 'post_id']);
        });

        Schema::create('secrets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('launch_code');
            $table->string('nuke_payload');
            $table->boolean('is_armed')->default(false);
            $table->timestamps();
        });

        Schema::create('data_types', function (Blueprint $table) {
            $driver = Schema::getConnection()->getDriverName();

            // PK you already had
            $table->uuid('id')->primary();
            $table->string('string'); // existing

            // Strings
            $table->char('char_col', 36);
            $table->string('string_100', 100);
            $table->text('text_col');
            $table->mediumText('medium_text_col');
            $table->longText('long_text_col');

            // Numbers
            $table->tinyInteger('tiny_integer_col');
            $table->unsignedTinyInteger('unsigned_tiny_integer_col');
            $table->smallInteger('small_integer_col');
            $table->unsignedSmallInteger('unsigned_small_integer_col');
            $table->mediumInteger('medium_integer_col');
            $table->unsignedMediumInteger('unsigned_medium_integer_col');
            $table->integer('integer_col');
            $table->unsignedInteger('unsigned_integer_col');
            $table->bigInteger('big_integer_col');
            $table->unsignedBigInteger('unsigned_big_integer_col');
            $table->decimal('decimal_col', 8, 2);
            $table->float('float_col', 8, 2);
            $table->double('double_col', 15, 8);
            $table->boolean('boolean_col');

            // Date & time
            $table->date('date_col');
            $table->time('time_col');
            $table->timeTz('time_tz_col')->nullable();
            $table->dateTime('datetime_col');
            $table->dateTimeTz('datetime_tz_col')->nullable();
            $table->timestamp('timestamp_col')->nullable();
            $table->timestampTz('timestamp_tz_col')->nullable();

            // JSON
            $table->json('json_col');
            if ($driver === 'pgsql') {
                $table->jsonb('jsonb_col');
            }

            // Network & IDs
            $table->ipAddress('ip_address_col');
            $table->macAddress('mac_address_col');
            $table->uuid('uuid_col');
            $table->ulid('ulid_col');

            // Binary
            $table->binary('binary_col');

            // Enum / Set / Year (DB-specific bits)
            $table->enum('enum_col', ['draft', 'published', 'archived'])->nullable();
            if ($driver === 'mysql') {
                $table->set('set_col', ['red','green','blue'])->nullable();
                $table->year('year_col')->nullable();
            } else {
                // Reasonable cross-DB fallback
                $table->unsignedSmallInteger('year_col')->nullable();
            }

            // Geometry (MySQL & Postgres w/ PostGIS)
            if (in_array($driver, ['mysql', 'pgsql'], true)) {
                $table->geometry('geom_col')->nullable();
                $table->point('point_col')->nullable();
                $table->lineString('line_string_col')->nullable();
                $table->polygon('polygon_col')->nullable();
                $table->geometryCollection('geometry_collection_col')->nullable();
                $table->multiPoint('multi_point_col')->nullable();
                $table->multiLineString('multi_line_string_col')->nullable();
                $table->multiPolygon('multi_polygon_col')->nullable();
            }

            // Morphs
            $table->morphs('morphable');
            $table->nullableMorphs('nullable_morphable');
            $table->uuidMorphs('uuid_morphable');
            $table->ulidMorphs('ulid_morphable');

            // Foreign IDs (no FK constraints here to keep it standalone)
            $table->foreignId('user_id')->nullable();
            $table->foreignUuid('team_uuid')->nullable();
            $table->foreignUlid('project_ulid')->nullable();

            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('number');
            $table->string('text');
            $table->double('total')->nullable();
            $table->foreignUuid('owner_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->timestampsTz();
            $table->softDeletesTz();
        });

        Schema::create('order_rows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')
                ->references('id')
                ->on('orders')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->string('specification');
            $table->double('quantity')->nullable();
            $table->double('price')->nullable();
            $table->double('total')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();
        });

        Schema::create('order_row_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_row_id')
                ->references('id')
                ->on('order_rows')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->string('specification');
            $table->double('quantity');
            $table->timestampsTz();
            $table->softDeletesTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('category_post');
        Schema::dropIfExists('secrets');
        Schema::dropIfExists('data_types');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('order_rows');
        Schema::dropIfExists('order_row_entries');
    }
};
