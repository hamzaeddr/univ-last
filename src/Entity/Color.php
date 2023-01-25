<?php

namespace App\Entity;

use App\Repository\ColorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ColorRepository::class)]
class Color
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $designation;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $couleur;

    #[ORM\OneToMany(mappedBy: 'color', targetEntity: PlEmptime::class)]
    private $emptimes;

    public function __construct()
    {
        $this->emptimes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDesignation(): ?string
    {
        return $this->designation;
    }

    public function setDesignation(?string $designation): self
    {
        $this->designation = $designation;

        return $this;
    }

    public function getCouleur(): ?string
    {
        return $this->couleur;
    }

    public function setCouleur(?string $couleur): self
    {
        $this->couleur = $couleur;

        return $this;
    }

    /**
     * @return Collection|PlEmptime[]
     */
    public function getEmptimes(): Collection
    {
        return $this->emptimes;
    }

    public function addEmptime(PlEmptime $emptime): self
    {
        if (!$this->emptimes->contains($emptime)) {
            $this->emptimes[] = $emptime;
            $emptime->setColor($this);
        }

        return $this;
    }

    public function removeEmptime(PlEmptime $emptime): self
    {
        if ($this->emptimes->removeElement($emptime)) {
            // set the owning side to null (unless already changed)
            if ($emptime->getColor() === $this) {
                $emptime->setColor(null);
            }
        }

        return $this;
    }
}
