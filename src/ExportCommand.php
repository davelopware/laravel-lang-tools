<?php namespace Tlr\LaravelLangTools;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ExportCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'lang:export';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Export and format translatable lines.';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		$data = $this->getData( $this->getLanguages() );

		$this->{'format' . ucfirst( $this->option('format') )}( $this->compress( $data ) );
	}

	/**
	 * Get the given languages' translation data
	 * @param  array $languages
	 * @return array
	 */
	public function getData( $languages )
	{
		$data = array();

		foreach ((array)$languages as $language)
		{
			$data[$language] = array_dot( $this->getTranslations( $this->getLanguageNamespaces( $language ), $language ) );
		}

		return $data;
	}

	/**
	 * Get all language slugs
	 * @return array
	 */
	public function getLanguages()
	{
		$languages = $this->option('lang');

		if (count($languages) === 0) {
			foreach ($this->laravel['files']->directories(app_path( 'lang' )) as $folder)
			{
				$languages[] = basename($folder);
			}
		}

		return $languages;
	}

	/**
	 * List the contents of the en directory
	 * @return array
	 */
	public function getLanguageNamespaces( $language = 'en' )
	{
		$namespaces = array();

		foreach( $this->laravel['files']->files(app_path( "lang/{$language}" )) as $file )
		{
			$namespaces[] = preg_replace('/\.php$/', '', basename($file));
		}

		return $namespaces;
	}

	/**
	 * Get translations for the given namespace(s)
	 * @param  array   $namespaces
	 * @return array
	 */
	public function getTranslations( $namespaces, $language = 'en' )
	{
		$translations = array();

		foreach ((array) $namespaces as $namespace)
		{
			$translations[$namespace] = $this->laravel['translator']->get($namespace, array(), $language);
		}

		return $translations;
	}

	/**
	 * Get the header columns for the export
	 * @return array
	 */
	public function getHeaders()
	{
		return array_merge(['key'], $this->getLanguages());
	}

	/**
	 * Compress the array into [ 'key', 'value', 'value'... ] format
	 * @param  array   $data
	 * @return array
	 */
	public function compress( $data )
	{
		$compressed = array();

		$xi = 1;

		foreach($data as $languageKey => $translations)
		{
			foreach ($translations as $key => $value)
			{
				if ( !isset($compressed[$key]) )
				{
					$compressed[$key] = array($key);
				}

				$compressed[$key][$xi] = $value;
			}

			$xi++;
		}

		return $compressed;
	}

	/**
	 * Format the output as CSV
	 * @param  array   $data
	 */
	public function formatCsv( $data )
	{
		$output = '';

		/// HEADERS ///

		$headers = $this->getHeaders();

		$cols = count( $this->getHeaders() );

		foreach( $headers as $key => $value )
		{
			$headers[$key] = "\"{$value}\"";
		}

		$this->line( implode(',', $headers) );

		/// VALUES ///

		foreach ($data as $row)
		{
			$rowData = array();

			$quoteEscaping = ( ! $this->option('quote-escaping-off'));
			$blankCellFound = false;
			$untranslatedRowFound = false;
			
			for ($xi = 0; $xi < $cols; $xi++)
			{
				$cell = array_get( $row, $xi );
				if (empty(trim($cell))) {
					$blankCellFound = true;
				}
				if ($quoteEscaping) {
					$cell = str_replace('"', '""', $cell); // escape double quotes with another quote (for excel)
				}
				$rowData[] = "\"$cell\"";
				
				if ($cols === 3 && $xi === 2 && $rowData[1] === $rowData[2]) {
					$untranslatedRowFound = true;
				}
			}

			$filterNotSpecified = ! ($this->option('untranslated') || $this->option('missing'));
			$untranslatedFilterPass = ($this->option('untranslated') && $untranslatedRowFound);
			$missingFilterPass = ($this->option('missing') && $blankCellFound);
			$filterPass = $untranslatedFilterPass || $missingFilterPass;
			if ($filterNotSpecified || $filterPass) {
				$this->line( implode(',', $rowData) );
			}
		}
	}

	/**
	 * Format the data as a table
	 * @param  array   $data
	 */
	public function formatTable( $data )
	{
		$table = $this->getHelperSet()->get('table');

		$table->setHeaders( $this->getHeaders() );
		$table->setRows( $data );

		$table->render( $this->getOutput() );
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			array('format', 'f', InputOption::VALUE_OPTIONAL, 'The format to display. table | csv', 'table'),
			array('missing', 'm', InputOption::VALUE_NONE, 'Only output rows with missing translations (csv mode only).'),
			array('untranslated', 'u', InputOption::VALUE_NONE, 'Only output rows untranslated values ie identical translations (csv mode only, and only with 2 languages).'),
			array('quote-escaping-off', 'nqe', InputOption::VALUE_NONE, 'Turn off escaping of double quotes (csv mode only)'),
			array('lang', 'L', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Export the specified language (can be used multiple times)'),
		);
	}

}
