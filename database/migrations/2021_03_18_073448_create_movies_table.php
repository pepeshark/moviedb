<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMoviesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->unsignedInteger('runtime')->nullable();
            $table->date('release_date')->nullable();
            $table->string('poster_path')->nullable();
            $table->text('overview')->nullable();
            $table->unsignedBigInteger('tmdb_id');
            $table->string('tmdb_url');
            $table->decimal('tmdb_vote_avg', 2,1)->default(0);
            $table->unsignedInteger('tmdb_vote_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('movies');
    }
}
