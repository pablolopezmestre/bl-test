<?php

namespace App\Service;

use App\Entity\Genre;
use App\Repository\GenreRepository;

class GenreCrudService
{
    private GenreRepository $genreRepository;

    public function __construct(GenreRepository $genreRepository)
    {
        $this->genreRepository = $genreRepository;
    }

    public function importFromCsv(string $rowString, string $delimiter = ','): array
    {
        $genres = [];
        $rowItems = explode($delimiter, $rowString);

        foreach ($rowItems as $item) {
            $item = trim($item);

            $genre = $this->findByName($item) ?? new Genre();
            $genre->setName($item);
            $this->save($genre);

            $genres[] = $genre;
        }

        return $genres;
    }

    private function findByName(string $name): ?Genre
    {
        return $this->genreRepository->findOneBy(['name' => $name]);
    }

    private function save(Genre $genre): Genre
    {
        $this->genreRepository->add($genre);

        return $genre;
    }
}
