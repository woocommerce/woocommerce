<?php
namespace Composer\Installers;

/**
 * An installer to handle TAO extensions.
 */
class TaoInstaller extends BaseInstaller
{
    protected $locations = array(
        'extension' => '{$name}'
    );
}
