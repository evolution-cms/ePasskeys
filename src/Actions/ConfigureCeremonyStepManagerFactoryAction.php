<?php

namespace EvolutionCMS\ePasskeys\Actions;

use EvolutionCMS\ePasskeys\Support\Config;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Illuminate\Http\Request;

class ConfigureCeremonyStepManagerFactoryAction
{
    public function execute(): CeremonyStepManagerFactory
    {
        $factory = new CeremonyStepManagerFactory();

        $request = null;
        if (function_exists('request')) {
            $request = request();
        }

        if ($request instanceof Request) {
            $allowedOrigins = Config::getAllowedOrigins($request);
            if ($allowedOrigins !== []) {
                $factory->setAllowedOrigins($allowedOrigins, false);
            }
        }

        return $factory;
    }
}
