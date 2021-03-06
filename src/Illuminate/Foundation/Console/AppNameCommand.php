<?php namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Foundation\Composer;
use Symfony\Component\Finder\Finder;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputArgument;

class AppNameCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'app:name';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = "Set the application namespace";

	/**
	 * The Composer class instance.
	 *
	 * @var \Illuminate\Foundation\Composer
	 */
	protected $composer;

	/**
	 * The filesystem instance.
	 *
	 * @var \Illuminate\Filesystem\Filesystem
	 */
	protected $files;

	/**
	 * Create a new key generator command.
	 *
	 * @param  \Illuminate\Filesystem\Filesystem  $files
	 * @return void
	 */
	public function __construct(Composer $composer, Filesystem $files)
	{
		parent::__construct();

		$this->files = $files;
		$this->composer = $composer;
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$this->setAppDirectoryNamespace();

		$this->setConfigNamespaces();

		$this->setComposerNamespace();

		$this->info('Application namespace set!');

		$this->composer->dumpAutoloads();
	}

	/**
	 * Set the namespace on the files in the app directory.
	 *
	 * @return void
	 */
	protected function setAppDirectoryNamespace()
	{
		$files = Finder::create()
                            ->in($this->laravel['path'])
                            ->exclude($this->laravel['path'].'/Http/Views')
                            ->name('*.php');

		foreach ($files as $file)
		{
			$this->replaceNamespace($file->getRealPath());
		}

		$this->setServiceProviderNamespaceReferences();
	}

	/**
	 * Replace the App namespace at the given path.
	 *
	 * @param  string  $path;
	 */
	protected function replaceNamespace($path)
	{
		$this->replaceIn(
			$path, 'namespace '.$this->root().';', 'namespace '.$this->argument('name').';'
		);

		$this->replaceIn(
			$path, 'namespace '.$this->root().'\\', 'namespace '.$this->argument('name').'\\'
		);
	}

	/**
	 * Set the referenced namespaces in various service providers.
	 *
	 * @return void
	 */
	protected function setServiceProviderNamespaceReferences()
	{
		$this->setReferencedFilterNamespaces();

		$this->setReferencedConsoleNamespaces();
	}

	/**
	 * Set the namespace on the referenced filters in the filter service provider.
	 *
	 * @return void
	 */
	protected function setReferencedFilterNamespaces()
	{
		$this->replaceIn(
			$this->laravel['path'].'/Providers/FilterServiceProvider.php',
			$this->root().'\\Http\\Filters', $this->argument('name').'\\Http\\Filters'
		);
	}

	/**
	 * Set the namespace on the referenced commands in the Artisan service provider.
	 *
	 * @return void
	 */
	protected function setReferencedConsoleNamespaces()
	{
		$this->replaceIn(
			$this->laravel['path'].'/Providers/ArtisanServiceProvider.php',
			$this->root().'\\Console', $this->argument('name').'\\Console'
		);
	}

	/**
	 * Set the PSR-4 namespace in the Composer file.
	 *
	 * @return void
	 */
	protected function setComposerNamespace()
	{
		$this->replaceIn(
			$this->getComposerPath(), $this->root().'\\\\', $this->argument('name').'\\\\'
		);
	}

	/**
	 * Set the namespace in the appropriate configuration files.
	 *
	 * @return void
	 */
	protected function setConfigNamespaces()
	{
		$this->setAppConfigNamespaces();

		$this->setAuthConfigNamespace();

		$this->setNamespaceConfigNamespace();
	}

	/**
	 * Set the application provider namespaces.
	 *
	 * @return void
	 */
	protected function setAppConfigNamespaces()
	{
		$this->replaceIn(
			$this->getConfigPath('app'), $this->root().'\\Providers', $this->argument('name').'\\Providers'
		);

		$this->replaceIn(
			$this->getConfigPath('app'), $this->root().'\\Http\\Controllers\\', $this->argument('name').'\\Http\\Controllers\\'
		);
	}

	/**
	 * Set the authentication User namespace.
	 *
	 * @return void
	 */
	protected function setAuthConfigNamespace()
	{
		$this->replaceIn(
			$this->getAuthConfigPath(), $this->root().'\\User', $this->argument('name').'\\User'
		);
	}

	/**
	 * Set the namespace configuration file namespaces.
	 *
	 * @return void
	 */
	protected function setNamespaceConfigNamespace()
	{
		$this->replaceIn(
			$this->getNamespaceConfigPath(), $this->root().'\\', $this->argument('name').'\\'
		);
	}

	/**
	 * Replace the given string in the given file.
	 *
	 * @param  string  $path
	 * @param  string  $search
	 * @param  string  $replace
	 * @return void
	 */
	protected function replaceIn($path, $search, $replace)
	{
		$this->files->put($path, str_replace($search, $replace, $this->files->get($path)));
	}

	/**
	 * Get the root namespace for the application.
	 *
	 * @return string
	 */
	protected function root()
	{
		return trim($this->laravel['config']['namespaces.root'], '\\');
	}

	/**
	 * Get the path to the Core User class.
	 *
	 * @return string
	 */
	protected function getUserClassPath()
	{
		return $this->laravel['path'].'/Core/User.php';
	}

	/**
	 * Get the path to the Composer.json file.
	 *
	 * @return string
	 */
	protected function getComposerPath()
	{
		return $this->laravel['path.base'].'/composer.json';
	}

	/**
	 * Get the path to the given configuration file.
	 *
	 * @param  string  $name
	 * @return string
	 */
	protected function getConfigPath($name)
	{
		return $this->laravel['path.config'].'/'.$name.'.php';
	}

	/**
	 * Get the path to the authentication configuration file.
	 *
	 * @return string
	 */
	protected function getAuthConfigPath()
	{
		return $this->getConfigPath('auth');
	}

	/**
	 * Get the path to the namespace configuration file.
	 *
	 * @return string
	 */
	protected function getNamespaceConfigPath()
	{
		return $this->getConfigPath('namespaces');
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			array('name', InputArgument::REQUIRED, 'The desired namespace.'),
		);
	}

}
