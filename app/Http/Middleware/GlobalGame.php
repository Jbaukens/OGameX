<?php

namespace OGame\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OGame\Services\ObjectService;
use OGame\Services\PlayerService;
use OGame\Services\SettingsService;
use Throwable;

class GlobalGame
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     * @throws Throwable
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (Auth::check()) {
            // Get objects.
            $object = new ObjectService();
            app()->instance(ObjectService::class, $object);

            // Instantiate settings service.
            $settings = app()->make(SettingsService::class);
            app()->instance(SettingsService::class, $settings);

            // Load player.
            $player = app()->make(PlayerService::class, ['player_id' => $request->user()->id]);
            app()->instance(PlayerService::class, $player);

            // Check if current planet change querystring parameter exists, if so, change current planet.
            if (!empty($request->query('cp'))) {
                $player->setCurrentPlanetId($request->query('cp'));
            }

            // Update player.
            $player->update();

            // Update current planet of player.
            $player->planets->current()->update();

            // Update all fleet missions of player that are associated with any of the player's planets.
            $player->updateFleetMissions();
        }

        return $next($request);
    }
}
