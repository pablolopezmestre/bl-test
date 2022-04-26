<?php

namespace App\Command;

use App\Entity\Movie;
use App\Repository\ActorRepository;
use App\Service\DirectorCrudService;
use App\Service\GenreCrudService;
use App\Repository\MovieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class ImportCsvCommand extends Command
{
    protected static $defaultName = 'app:import-csv';
    private const FILE_DATA_PATH = __DIR__ . '/../../data/';

    private ActorRepository $actorRepository;
    private DirectorCrudService $directorService;
    private GenreCrudService $genreService;
    private MovieRepository $movieRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        ActorRepository $actorRepository,
        DirectorCrudService $directorService,
        GenreCrudService $genreService,
        MovieRepository $movieRepository
    ) {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->actorRepository = $actorRepository;
        $this->directorService = $directorService;
        $this->genreService = $genreService;
        $this->movieRepository = $movieRepository;
    }

    protected function configure(): void
    {
        $this->addArgument('fileName', InputArgument::REQUIRED, 'Nombre fichero datos');

        $this->setDescription('Este comando importa datos de un CSV...');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $fileName = self::FILE_DATA_PATH . $input->getArgument('fileName');

        if (!$this->dataExists($fileName)) {
            $io->getErrorStyle()->error('No se ha encontrado el archivo de datos.');

            return Command::INVALID;
        }

        $io->title('Importando datos...');

        $films = $this->parseCSV($fileName);
        $em = $this->entityManager;
        // Having an SQL logger enabled when processing batches can have a serious impact on performance
        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        $addedMovies = 0;
        $updatedMovies = 0;
        $batchSize = 20;

        $progressBar = new ProgressBar($io, count($films));
        $progressBar->start();

        foreach ($films as $key => $film) {
            $genres = $this->genreService->importFromCsv($film['genre']);
            $directors = $this->directorService->importFromCsv($film['director']);
            $actors = $this->insertOrUpdateRelations($this->actorRepository, $film['actors']);

            $movie = $this->movieRepository->findOneBy(['title' => trim($film['title'])]) ?: new Movie();
            $movie->getId() ? $updatedMovies++ : $addedMovies++;

            $movie->setTitle($film['title'] ?? '');
            $movie->setPublishDate(new \DateTime($film['date_published'] ?? ''));
            $movie->addGenres($genres);
            $movie->setDuration($film['duration'] ?? 0);
            $movie->setProducer($film['production_company'] ?? '');
            if (! empty($actors)) {
                $movie->addActor($actors);
            }
            $movie->addDirector($directors);

            $em->persist($movie);

            // For performance reasons, batch the SQL queries
            if (($key % $batchSize) === 0) {
                $em->flush();
                $em->clear();
            }

            $progressBar->advance();
        }

        $em->flush();
        $em->clear();

        $progressBar->finish();

        $io->success(sprintf('Se han añadido %d películas y se han actualizado %d.', $addedMovies, $updatedMovies));

        return Command::SUCCESS;
    }

    /**
     * Check if data/filename file exists.
     *
     * @param string $fileName 
     *
     * @return bool
     */
    private function dataExists(string $fileName): bool
    {
        return file_exists($fileName);
    }

    /**
     * Parse a csv file
     *
     * @param string $fileName
     *
     * @return array
     */
    private function parseCSV(string $fileName): array
    {
        $context = [
            CsvEncoder::DELIMITER_KEY => ',',
            CsvEncoder::ENCLOSURE_KEY => '"',
            CsvEncoder::ESCAPE_CHAR_KEY => '\\',
        ];

        $serializer = new Serializer([new ObjectNormalizer()], [new CsvEncoder()]);

        return $serializer->decode(file_get_contents($fileName), 'csv', $context);
    }
}
