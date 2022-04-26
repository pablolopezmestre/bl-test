<?php

namespace App\Command;

use App\Entity\Actor;
use App\Entity\Director;
use App\Entity\Genre;
use App\Entity\Movie;
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

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('fileName', InputArgument::REQUIRED, 'Nombre fichero datos');

        $this->setDescription('Este comando importa datos de un CSV...');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $fileName = $input->getArgument('fileName');

        if (!$this->dataExists($fileName)) {
            $io->getErrorStyle()->error('No se ha encontrado el archivo de datos.');

            return Command::INVALID;
        }

        $io->title('Importando datos...');

        $movies = $this->parseCSV();

        $genre_repository = $this->entityManager->getRepository(Genre::class);
        $actor_repository = $this->entityManager->getRepository(Actor::class);
        $director_repository = $this->entityManager->getRepository(Director::class);
        $movie_repository = $this->entityManager->getRepository(Movie::class);

        $added_movies = 0;
        $updated_movies = 0;

        $progressBar = new ProgressBar($io, count($movies));
        $progressBar->start();

        $this->entityManager->getConnection()->getConfiguration()->setSQLLogger(null);

        foreach ($movies as $movie) {
            $genres = $this->insertOrUpdateRelations($genre_repository, $movie['genre']);
            $actors = $this->insertOrUpdateRelations($actor_repository, $movie['actors']);
            $directors = $this->insertOrUpdateRelations($director_repository, $movie['director']);

            $entity_object = $movie_repository->findOneBy(['title' => trim($movie['title'])]) ?: new Movie();

            if ($entity_object->getId()) {
                $updated_movies++;
            } else {
                $added_movies++;
            }

            $entity_object->setTitle($movie['title'] ?? '');
            $entity_object->setPublishDate(new \DateTime($movie['date_published'] ?? ''));
            if (! empty($genres)) {
                $entity_object->addGenres($genres);
            }
            $entity_object->setDuration($movie['duration'] ?? 0);
            $entity_object->setProducer($movie['production_company'] ?? '');
            if (! empty($actors)) {
                $entity_object->addActor($actors);
            }
            if (! empty($directors)) {
                $entity_object->addDirector($directors);
            }

            $this->entityManager->persist($entity_object);
            $this->entityManager->flush();
            $this->entityManager->clear();
            unset($entity_object);
            unset($movie);

            $progressBar->advance();
        }

        $progressBar->finish();

        $io->success(sprintf('Se han añadido %d películas y se han actualizado %d.', $added_movies, $updated_movies));

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
        return file_exists(self::FILE_DATA_PATH . $fileName);
    }

    /**
     * Parse a csv file
     *
     * @return array
     */
    private function parseCSV(): array
    {
        $context = [
            CsvEncoder::DELIMITER_KEY => ',',
            CsvEncoder::ENCLOSURE_KEY => '"',
            CsvEncoder::ESCAPE_CHAR_KEY => '\\',
        ];

        $serializer = new Serializer([new ObjectNormalizer()], [new CsvEncoder()]);

        return $serializer->decode(file_get_contents(self::FILE_DATA_PATH), 'csv', $context);
    }

    /**
     * Insert or update a relation
     *
     * @param object $repository
     * @param string $relation
     */
    public function insertOrUpdateRelations(object $repository, string $items)
    {
        $name =  $repository->getClassName();
        $items = explode(',', $items);

        foreach ($items as $item) {
            $item = trim($item);

            $entity_object = $repository->findOneBy(['name' => $item]) ?: new $name();
            $entity_object->setName($item);

            $this->entityManager->persist($entity_object);
            $this->entityManager->flush();
        }

        return $entity_object ?? null;
    }
}
