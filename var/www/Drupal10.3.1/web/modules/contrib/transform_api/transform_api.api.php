<?php

/**
 * @file
 * Hooks related to Transform API module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter all transformation arrays as they are transformed.
 *
 * @param array $transformation
 *   The transformation array of the transform.
 */
function hook_transform_alter(&$transformation) {

}

/**
 * Alter transformation arrays with a specific HOOK identifier.
 *
 * @param array $transformation
 *   The transformation array of the transform.
 */
function hook_HOOK_transform_alter(&$transformation) {

}

/**
 * Alter the configuration of transform blocks.
 *
 * @param array $blocks
 *   The array of transform blocks divided into regions.
 */
function hook_blocks_transform_config_alter(&$blocks) {

}

/**
 * @} End of "addtogroup hooks".
 */
