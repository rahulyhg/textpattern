<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2017 The Textpattern Development Team
 *
 * This file is part of Textpattern.
 *
 * Textpattern is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation, version 2.
 *
 * Textpattern is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Textpattern. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * Skin Base
 *
 * Extended by Skin and AssetBase.
 *
 * @since   4.7.0
 * @package Skin
 */

namespace Textpattern\Skin {

    abstract class SkinBase extends SkinsBase implements SkinAssetInterface
    {
        /**
         * The skin to work with.
         *
         * @var string
         * @see setSkin()
         */

        protected $skin;

        /**
         * Caches whether the skin related row exists.
         *
         * @var bool
         * @see isInstalled()
         */

        protected $isInstalled = null;

        /**
         * Caches whether the skin is used by any section.
         *
         * @var bool
         * @see skinIsInUse()
         */

        protected $isInUse = null;

        /**
         * Whether the skin is locked via a 'lock' directory or not.
         *
         * @var string
         * @see lockSkin(), unlockSkin()
         */

        protected $locked = false;

        /**
         * Constructor.
         *
         * @param string $skin  The skin name (set the related property);
         * @param array  $infos Skin infos (set the related property).
         */

        public function __construct($skin = null)
        {
            $skin ? $this->setSkin($skin) : '';
        }

        /**
         * Set the skin property
         */

        public function setSkin($skin)
        {
            $this->skin = strtolower(sanitizeForUrl($skin));
            $this->isInstalled = null;
            $this->isInUse = null;
        }

        /**
         * {@inheritdoc}
         */

        final public function skinIsInstalled()
        {
            if ($this->isInstalled === null) {
                $this->isInstalled = self::isInstalled($this->skin);
            }

            return $this->isInstalled;
        }

        /**
         * Whether a skin row exists or not.
         *
         * @return bool
         */

        public static function isInstalled($skin)
        {
            $inInstalled = static::$installed ? array_key_exists($skin, static::$installed) : false;

            if ($inInstalled) {
                return $inInstalled;
            } else {
                return (bool) safe_field('name', 'txp_skin', "name ='".doSlash($skin)."'");
            }
        }

        /**
         * Checks if a skin directory exists and is readable.
         *
         * @return bool
         */

        public function isReadable($path = null)
        {
            $path = $this->getPath($path);

            return self::isType($path) && is_readable($path);
        }

        /**
         * Checks if the Skin directory exists and is writable;
         * if not, creates it.
         *
         * @param  string $path See getPath().
         * @return bool
         */

        public function isWritable($path = null)
        {
            $path = $this->getPath($path);

            return self::isType($path) && is_writable($path);
        }

        /**
         * Checks if a directory or file exists.
         *
         * @return bool
         */

        public static function isType($path)
        {
            $isFile = pathinfo($path, PATHINFO_EXTENSION);
            $isType = $isFile ? is_file($path) : is_dir($path);

            return $isType;
        }

        /**
         * {@inheritdoc}
         */

        final public function lockSkin()
        {
            $time_start = microtime(true);

            if ($this->locked) {
                return true;
            }

            while (!($locked = $this->mkDir('lock')) && $time < 3) {
                sleep(0.5);
                $time = microtime(true) - $time_start;
            }

            if ($locked) {
                $this->locked = true;
                return $locked;
            }

            $this->setResults(gtxt('unable_to_lock_skin'));
        }

        /**
         * {@inheritdoc}
         */

        final public function mkDir($path = null)
        {
            $path = $this->getPath($path);

            if (@mkdir($path)) {
                return true;
            }

            $this->setResults(
                gtxt('directory_creation_failure', array('{name}' => basename($path)))
            );
        }

        /**
         * {@inheritdoc}
         */

        final public function unlockSkin()
        {
            $unlocked = $this->rmDir('lock');

            if ($unlocked) {
                $this->locked = false;
                return true;
            }

            $this->setResults(gtxt("unable_to_unlock_the_skin_directory"));
        }

        /**
         * {@inheritdoc}
         */

        final public function rmDir($path = null)
        {
            $path = $this->getPath($path);

            if (@rmdir($path)) {
                return true;
            }

            $this->setResults(
                gtxt('directory_deletion_failure', array('{name}' => basename($path)))
            );
        }

        /**
         * {@inheritdoc}
         */

        public static function getBasePath()
        {
            return get_pref('skin_base_path');
        }

        /**
         * {@inheritdoc}
         */

        public function getPath($path = null)
        {
            return self::getBasePath().'/'.$this->skin.($path ? '/'.$path : '');
        }
    }
}
