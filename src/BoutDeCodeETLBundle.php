<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle;

use BoutDeCode\ETLBundle\DependencyInjection\ETLExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class BoutDeCodeETLBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new ETLExtension();
    }
}
