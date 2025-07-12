<?php
namespace App\Entity;

use App\Repository\SurveillancePointRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SurveillancePointRepository::class)]
class SurveillancePoint
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'points')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Zone $zone = null;

    #[ORM\Column(type: 'integer')]
    private ?int $population = null;

    #[ORM\Column(type: 'integer')]
    private ?int $symptomatic = null;

    #[ORM\Column(type: 'integer')]
    private ?int $positive = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getZone(): ?Zone
    {
        return $this->zone;
    }

    public function setZone(?Zone $zone): self
    {
        $this->zone = $zone;
        return $this;
    }

    public function getPopulation(): ?int
    {
        return $this->population;
    }

    public function setPopulation(int $population): self
    {
        $this->population = $population;
        return $this;
    }

    public function getSymptomatic(): ?int
    {
        return $this->symptomatic;
    }

    public function setSymptomatic(int $symptomatic): self
    {
        $this->symptomatic = $symptomatic;
        return $this;
    }

    public function getPositive(): ?int
    {
        return $this->positive;
    }

    public function setPositive(int $positive): self
    {
        $this->positive = $positive;
        return $this;
    }
}
