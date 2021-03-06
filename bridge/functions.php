<?php

namespace DDTrace\Bridge;

function dd_tracing_enabled()
{
    $value = getenv('DD_TRACE_ENABLED');
    if (false === $value) {
        // Not setting the env means we default to enabled.
        return true;
    }

    $value = trim(strtolower($value));
    if ($value === '0' || $value === 'false') {
        return false;
    } else {
        return true;
    }
}

/**
 * Checks if any of the provided classes exists.
 *
 * @param string[] $sentinelClasses
 * @return bool
 */
function any_class_exists(array $sentinelClasses)
{
    foreach ($sentinelClasses as $sentinelClass) {
        if (class_exists($sentinelClass)) {
            return true;
        }
    }

    return false;
}

/**
 * Traces spl_autoload_register in order to provide hooks for auto-instrumentation to be executed.
 */
function dd_wrap_autoloader()
{
    $dd_autoload_called = false;

    // Classes of autoloaders that triggers a check to un-register our hook into 'spl_autoload_register' function.
    $untraceTriggerClasses = [
        'Composer\Autoload\ClassLoader',
    ];

    // When a check to un-register our hook into 'spl_autoload_register' function is triggered, if any of the classes
    // listed here exists, then we un-register our spl_autoload_register wrapper.
    // This check is required because some frameworks (e.g. Zend 1) register autoloaders which are relevant to our
    // instrumentation after the one provided by composer.
    $sentinelClasses = [
        'Symfony\Component\HttpKernel\Kernel',
    ];

    // Instead of tracing autoloaders statically, we should trace them dynamically. This can be done at the moment
    // because of https://github.com/DataDog/dd-trace-php/issues/224 and the fact that in some cases, e.g. Symfony's
    // `Symfony\Component\Config\Resource\ClassExistenceResource::throwOnRequiredClass` loaders are private.
    // As soon as this is fixed we can trace `spl_autoload_register` function and use it as a hook instead of
    // statically hooking into a limited number of class loaders.
    dd_trace('spl_autoload_register', function () use (&$dd_autoload_called, $untraceTriggerClasses, $sentinelClasses) {
        $args = func_get_args();
        $originalAutoloaderRegistered = call_user_func_array('spl_autoload_register', $args);

        $loader = $args[0];

        $loaderClass = null;
        if (is_array($loader)) {
            if (is_string($loader[0])) {
                $loaderClass = $loader[0];
            } elseif (is_object($loader[0])) {
                $loaderClass = get_class($loader[0]);
            }
        }


        // Why unregistering spl_autoload_register?
        // In some cases (e.g. Symfony) this 'spl_autoload_register' function is called within a private scope and at
        // the moment we are working to have this use case properly handled by the extension. In the meantime we provide
        // this workaround.
        if (in_array($loaderClass, $untraceTriggerClasses)
            && any_class_exists($sentinelClasses)) {
            dd_untrace('spl_autoload_register');
        }

        if (!$dd_autoload_called) {
            $dd_autoload_called = true;
            require_once __DIR__ . '/dd_autoloader.php';
            spl_autoload_register(['\DDTrace\Bridge\Autoloader', 'load']);
        }

        require __DIR__ . '/dd_init.php';

        return $originalAutoloaderRegistered;
    });
}
