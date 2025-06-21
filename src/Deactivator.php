<?php

namespace AboveTheFold;

/**
 * Handles plugin deactivation tasks.
 *
 * This class is responsible for cleaning up scheduled tasks and other
 * temporary resources when the plugin is deactivated.
 */
class Deactivator
{
        /**
         * Run deactivation logic.
         *
         * Clears the scheduled cleanup event.
         */
        public static function deactivate()
        {
                Cleanup::deactivate();
        }
}
