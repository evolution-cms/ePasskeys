<?php

namespace EvolutionCMS\ePasskeys\Actions;

use Webauthn\CeremonyStep\CeremonyStepManagerFactory;

class ConfigureCeremonyStepManagerFactoryAction
{
    public function execute(): CeremonyStepManagerFactory
    {
        return new CeremonyStepManagerFactory();
    }
}
