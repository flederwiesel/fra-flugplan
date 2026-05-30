<?php

// Provide cache busting for any asset referred to from any PHP script.
// If project is served by a symlink pointing to the actual versioned files,
// we can use the real path for cache busting, i.e. to "invalidate" caches
// (especially browser cache, which we do not have control over before `max-age`)
// by providing the versioned and therefore unique path.
// In order to not require any patching in the (deployed) source files, we can
// inject the real path to these assets via the Asset::src() function,
// which replaces the symlinked path with the real one dynamically.

class Asset
{
	private static $assetDir;
	private static $prjRoot;

	private static function init()
	{
		// Get relative paths of script from request and file system.
		// Those will later be compared in `ref()`.
		$filename = $_SERVER["SCRIPT_FILENAME"];
		$realpath = str_replace("\\", "/", realpath($filename));
		$realpath = self::relpath($realpath);
		$filename = self::relpath($filename);

		self::rtrimEqual($filename, $realpath);

		self::$assetDir = $realpath;
		self::$prjRoot = $filename;
	}

	// Cut off common path components (separated by "/")
	private static function rtrimEqual(&$one, &$two)
	{
		$o = strlen($one);
		$t = strlen($two);
		$len = $o;
		$cut = 0;

		// Compare right to left, remember positions of "/"
		while ($o > 0 && $t > 0)
		{
			if ($one[--$o] !== $two[--$t])
				break;

			if ($one[$o] === "/")
				$cut = $o;
		}

		$cut -= $len;	// Will be negative -> remove from the right
		$one = substr($one, 0, $cut);
		$two = substr($two, 0, $cut);
	}

	// Return relative path
	private static function relpath($path)
	{
		$root = $_SERVER["DOCUMENT_ROOT"];
		$len = strlen($root);

		if (substr($path, 0, $len) !== $root)
			throw new ErrorException("Path is outside of DOCUMENT_ROOT.");

		return substr($path, $len);
	}

	// Normalize path by replacing ".", ".." and duplicate "/".
	private static function normalizePath($path)
	{
		if (!$path)
			return "";

		// Start array with an empty string, if path has a leading "/",
		// as we will use implode() later on to rebuild the path string.
		$parts = $path[0] == "/" ? [ "" ] : [];

		// Using strtok() we can eliminate consecutive "/" nicely :)
		$tok = strtok($path, "/");

		while ($tok !== false)
		{
			if ($tok !== ".")
			{
				if ($tok === "..")
				{
					// Keep leading "/" -- will be glueed to asset dir anyway
					if ($parts)
						if (count($parts) > 1 || strlen($parts[0]) > 0)
							array_pop($parts);
				}
				else
				{
					array_push($parts, $tok);
				}
			}

			$tok = strtok("/");
		}

		// If $path contained only slashes -> ""
		if (count($parts) == 1)
			if (!$parts[0])
				return "";

		return implode("/", $parts);
	}

	// Return path relative to prj dir symlink by asset path
	public static function src($path)
	{
		if (self::$assetDir)
		{
			// Check whether calling script is in `self::$prjRoot`
			$len = strlen(self::$prjRoot);
			$dir = dirname($_SERVER["SCRIPT_NAME"]);
			$symlink = substr($dir, 0, $len);

			if ($symlink === self::$prjRoot)
			{
				// Remove project root symlink from dir
				$subdir = substr($dir, $len);
				$path = self::$assetDir . self::normalizePath("{$subdir}/{$path}");
			}
		}

		return $path;
	}

	public static function selftest()
	{
		$test =
		[
			[ "", "" ],
			[ "/", "" ],
			[ "///", "" ],
			[ "foo/bar", "foo/bar" ],
			[ "/foo/bar", "/foo/bar" ],
			[ "/foo/bar/", "/foo/bar" ],
			[ "/foo/bar/baz/../img/foo.png", "/foo/bar/img/foo.png" ],
			[ "/foo/bar/baz/../../img/foo.png", "/foo/img/foo.png" ],
			[ "/foo/bar/baz/../../../img/foo.png", "/img/foo.png" ],
			[ "/foo/../../../img/foo.png", "/img/foo.png" ],
			[ "foo/../../../img/foo.png", "img/foo.png" ],
		];

		foreach ($test as list($path, $expect))
		{
			$flat = Asset::normalizePath($path);
			assert($flat === $expect, "'{$path}' -> '{$expect}'");
		}
	}
}

(static function()
{
	static::init();
})->bindTo(null, Asset::class)();

// Asset::selftest();

?>
