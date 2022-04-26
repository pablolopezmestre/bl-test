<?php

namespace App\Service;

use App\Entity\Actor;
use App\Repository\ActorRepository;

class ActorCrudService
{
    private ActorRepository $actorRepository;

    public function __construct(ActorRepository $actorRepository)
    {
        $this->actorRepository = $actorRepository;
    }

    public function importFromCsv(string $rowString, string $delimiter = ','): array
    {
        $actors = [];
        $rowItems = explode($delimiter, $rowString);

        foreach ($rowItems as $item) {
            $item = trim($item);

            $actor = $this->findByName($item) ?? new Actor();
            $actor->setName($item);
            $this->save($actor);

            $actors[] = $actor;
        }

        return $actors;
    }

    private function findByName(string $name): ?Actor
    {
        return $this->actorRepository->findOneBy(['name' => $name]);
    }

    private function save(Actor $actor): Actor
    {
        $this->actorRepository->add($actor);

        return $actor;
    }
}
