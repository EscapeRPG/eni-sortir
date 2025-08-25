<?php

namespace App\Entity;

use App\Repository\SortieRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SortieRepository::class)]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?\DateTime $startingDateHour = null;

    #[ORM\Column]
    private ?\DateTime $endDateHour = null;

    #[ORM\Column]
    private ?int $nbInscriptionsMax = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $eventInfo = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->nom;
    }

    public function setName(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getStartingDateHour(): ?\DateTime
    {
        return $this->startingDateHour;
    }

    public function setStartingDateHour(\DateTime $startingDateHour): static
    {
        $this->startingDateHour = $startingDateHour;

        return $this;
    }

    public function getEndDateHour(): ?\DateTime
    {
        return $this->endDateHour;
    }

    public function setEndDateHour(\DateTime $endDateHour): static
    {
        $this->endDateHour = $endDateHour;

        return $this;
    }

    public function getNbInscriptionsMax(): ?int
    {
        return $this->nbInscriptionsMax;
    }

    public function setNbInscriptionsMax(int $nbInscriptionsMax): static
    {
        $this->nbInscriptionsMax = $nbInscriptionsMax;

        return $this;
    }

    public function getEventInfo(): ?string
    {
        return $this->infosSortie;
    }

    public function setEventInfo(?string $eventInfo): static
    {
        $this->eventInfo = $eventInfo;

        return $this;
    }

}
