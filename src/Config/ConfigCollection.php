<?php
/**
 * Scabbia2 Config Component
 * https://github.com/eserozvataf/scabbia2
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link        https://github.com/eserozvataf/scabbia2-config for the canonical source repository
 * @copyright   2010-2016 Eser Ozvataf. (http://eser.ozvataf.com/)
 * @license     http://www.apache.org/licenses/LICENSE-2.0 - Apache License, Version 2.0
 */

namespace Scabbia\Config;

/**
 * ConfigCollection
 *
 * @package     Scabbia\Config
 * @author      Eser Ozvataf <eser@ozvataf.com>
 * @since       2.0.0
 */
class ConfigCollection
{
    /** @type int NONE      no flag */
    const NONE = 0;
    /** @type int OVERWRITE overwrite existing nodes by default */
    const OVERWRITE = 1;
    /** @type int FLATTEN   flatten nodes by default */
    const FLATTEN = 2;


    /** @type array configuration flags */
    public $configFlags = [
        "disabled" => false // always false
    ];
    /** @type array configuration content */
    public $content = [];


    /**
     * Adds a piece into configuration compilation
     *
     * @param string $uConfig        configuration
     * @param int    $uLoadingFlags  loading flags
     *
     * @return void
     */
    public function add($uConfig, $uLoadingFlags = self::NONE)
    {
        $this->process($this->content, $uConfig, $uLoadingFlags);
    }

    /**
     * Sets a configuration flag
     *
     * @param string $uName   name of the configuration flag
     * @param bool   $uValue  value
     *
     * @return void
     */
    public function setFlag($uName, $uValue)
    {
        $this->configFlags[$uName] = $uValue;
    }

    /**
     * Returns route information in order to store it
     *
     * @return array
     */
    public function save()
    {
        return $this->content;
    }

    /**
     * Processes the configuration file in order to simplify its accessibility
     *
     * @param mixed $uTarget        target reference
     * @param mixed $uNode          source object
     * @param int   $uLoadingFlags  loading flags
     *
     * @return void
     */
    protected function process(&$uTarget, $uNode, $uLoadingFlags)
    {
        $tQueue = [
            [[], $uNode, $uLoadingFlags, &$uTarget, null, false]
        ];

        do {
            $tItem = array_pop($tQueue);

            if ($tItem[4] === null) {
                $tRef = &$tItem[3];
            } else {
                $tRef = &$tItem[3][$tItem[4]];
            }

            if (is_scalar($tItem[1]) || $tItem[1] === null) {
                if (!isset($tRef) || ($tItem[2] & self::OVERWRITE) === self::OVERWRITE) {
                    $tRef = $tItem[1];
                }

                continue;
            }

            if (!is_array($tRef) || ($tItem[2] & self::OVERWRITE) === self::OVERWRITE) {
                $tRef = []; // initialize as an empty array
            }

            foreach ($tItem[1] as $tKey => $tSubnode) {
                $tFlags = $tItem[2];
                $tListNode = false;

                $tNodeParts = explode("|", $tKey);
                $tNodeKey = array_shift($tNodeParts);

                if ($tItem[5] && is_numeric($tNodeKey)) {
                    $tNodeKey = count($tRef);
                }

                foreach ($tNodeParts as $tNodePart) {
                    if (array_key_exists($tNodePart, $this->configFlags)) {
                        if ($this->configFlags[$tNodePart] !== true) {
                            continue 2;
                        }
                    } elseif ($tNodePart === "list") {
                        $tListNode = true;
                    } elseif ($tNodePart === "important") {
                        $tFlags |= self::OVERWRITE;
                    } elseif ($tNodePart === "flat") {
                        $tFlags |= self::FLATTEN;
                    }
                }

                $tNewNodeKey = $tItem[0];
                if (($tFlags & self::FLATTEN) === self::FLATTEN) {
                    $tNodeKey = ltrim("{$tItem[4]}/{$tNodeKey}", "/");
                    $tNewNodeKey[] = $tNodeKey;

                    $tQueue[] = [$tNewNodeKey, $tSubnode, $tFlags, &$tRef, $tNodeKey, $tListNode];
                } else {
                    $tNewNodeKey[] = $tNodeKey;
                    $tQueue[] = [$tNewNodeKey, $tSubnode, $tFlags, &$tRef[$tNodeKey], null, $tListNode];
                }
            }
        } while (count($tQueue) > 0);
    }
}
