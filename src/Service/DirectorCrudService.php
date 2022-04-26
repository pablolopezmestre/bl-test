<?php

namespace App\Service;

use App\Entity\Director;
use App\Repository\DirectorRepository;

class DirectorCrudService
{
    private DirectorRepository $directorRepository;

    public function __construct(DirectorRepository $directorRepository)
    {
        $this->directorRepository = $directorRepository;
    }

    public function importFromCsv(string $rowString, string $delimiter = ','): array
    {
        $directors = [];
        $rowItems = explode($delimiter, $rowString);

        foreach ($rowItems as $item) {
            $item = trim($item);

            $director = $this->findByName($item) ?? new Director();
            $director->setName($item);
            $this->save($director);

            $directors[] = $director;
        }

        return $directors;
    }

    private function findByName(string $name): ?Director
    {
        return $this->directorRepository->findOneBy(['name' => $name]);
    }

    private function save(Director $director): Director
    {
        $this->directorRepository->add($director);

        return $director;
    }
}
