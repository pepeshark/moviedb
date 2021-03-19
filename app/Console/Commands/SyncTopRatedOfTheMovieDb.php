<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Movie;
use App\Models\Director;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SyncTopRatedOfTheMovieDb extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'themoviedb:sync';

    protected $apiUrl = 'https://api.themoviedb.org/3';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (empty(config('app.movie_db_api_key'))) {
            $this->error('movie_db_api_key is empty!');
            return 1;
        }

        $insertedMovies = $this->pullTopRatedMovies();
        $this->info('Inserted movies: ' . $insertedMovies);

        $updatedMovies = $this->updateMoviesProperties();
        $this->info('Updated movies: ' . $updatedMovies);

        $insertedDirectors = $this->pullDirectors();
        $this->info('Inserted directors: ' . $insertedDirectors);

        $this->info('Sync process is done.');

        return 0;
    }

    private function pullTopRatedMovies()
    {
        $page = 1;
        $moviesCount = 0;
        $totalMoviesCount = 525;
        $movies = [];
        while (count($movies) <= $totalMoviesCount) {
            $response = Http::get($this->apiUrl . '/movie/top_rated', [
                'api_key' => config('app.movie_db_api_key'),
                'page' => $page,
            ]);

            if ($response->successful()) {
                $responseBody = json_decode($response->body());
                if (isset($responseBody->results)) {
                    $movie = [];
                    foreach ($responseBody->results as $m) {
                        $movie = [
                            'title' => $m->original_title,
                            //'runtime' - from /movie endpoint
                            'release_date' => $m->release_date,
                            'poster_path' => $m->poster_path,
                            'overview' => $m->overview,
                            'tmdb_id' => $m->id,
                            'tmdb_url' => 'https://www.themoviedb.org/movie/' . $m->id,
                            'tmdb_vote_avg' => $m->vote_average,
                            'tmdb_vote_count' => $m->vote_count,
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                        ];
                        array_push($movies, $movie);
                    }
                }
            } else {
                $this->error('APi call is faild, status: ' . $response->status());
            }
            $page++;
        }
        $movies = array_slice($movies, 0, $totalMoviesCount);
        $moviesCount = count($movies);
        if ($moviesCount != $totalMoviesCount) {
            $this->error('Movies count is not same as ' . $totalMoviesCount);
            return 1;
        } else {
            Movie::truncate();
            Movie::insert($movies);
        }
        return $moviesCount;
    }

    private function updateMoviesProperties()
    {
        $updateCount = 0;
        $movies = Movie::ZeroRuntime()->get();
        foreach ($movies as $movie) {
            $response = Http::get($this->apiUrl . '/movie/' . $movie->tmdb_id, [
                'api_key' => config('app.movie_db_api_key'),
            ]);

            if ($response->successful()) {
                $responseBody = json_decode($response->body());
                if (!empty($responseBody)) {
                    $movie->runtime = $responseBody->runtime;
                    $movie->save();
                    $updateCount++;
                } else {
                    $this->info('Response body is empty, id:' . $movie->tmdb_id);
                }

            } else {
                $this->error('APi call is faild, status: ' . $response->status());
            }
        }
        return $updateCount;
    }

    private function pullDirectors()
    {
        DB::table('director_movie')->truncate();
        Director::truncate();
        $directorCount = 0;
        $movies = Movie::all();
        foreach ($movies as $movie) {

            $response = Http::get($this->apiUrl . '/movie/' . $movie->tmdb_id . '/credits', [
                'api_key' => config('app.movie_db_api_key'),
            ]);

            if ($response->successful()) {
                $responseBody = json_decode($response->body());
                if (isset($responseBody)) {
                    $director = [];
                    foreach ($responseBody->crew as $d) {
                        if ($d->job == 'Director') {
                            $director = new Director();
                            $director->name = $d->original_name;
                            $director->tmdb_id = $d->id;
                            $director->created_at = Carbon::now();
                            $director->updated_at = Carbon::now();
                            // TODO: get birthday property from somewhere
                            //'birthday' => $d->release_date,
                            // TODO: get bio property from somewhere
                            //'bio' => $d->bio,
                            $director->save();
                            $director->movies()->save($movie);
                            $directorCount++;
                        }
                    }
                }
            } else {
                $this->error('APi call is faild, status: ' . $response->status());
            }
        }
        return $directorCount;
    }
}
