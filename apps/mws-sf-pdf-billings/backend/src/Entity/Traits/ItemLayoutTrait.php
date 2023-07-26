<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

// Biblio :
// https://medium.com/@galopintitouan/using-traits-to-compose-your-doctrine-entities-9b516335119b
namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

trait ItemLayoutTrait {
  #[ORM\Column(nullable: true)]
  private ?bool $insertPageBreakBefore = null;

  #[ORM\Column(length: 255, nullable: true)]
  private ?string $marginTop = null;

  #[ORM\Column(nullable: true)]
  private ?bool $insertPageBreakAfter = null;

  #[ORM\Column(length: 255, nullable: true)]
  private ?string $marginBottom = null;

  public function getMarginTop(): ?string
  {
      return $this->marginTop;
  }

  public function setMarginTop(?string $marginTop): static
  {
      $this->marginTop = $marginTop;

      return $this;
  }

  public function isInsertPageBreakBefore(): ?bool
  {
      return $this->insertPageBreakBefore;
  }

  public function setInsertPageBreakBefore(?bool $insertPageBreakBefore): static
  {
      $this->insertPageBreakBefore = $insertPageBreakBefore;

      return $this;
  }

  public function getMarginBottom(): ?string
  {
      return $this->marginBottom;
  }

  public function setMarginBottom(?string $marginBottom): static
  {
      $this->marginBottom = $marginBottom;

      return $this;
  }

  public function isInsertPageBreakAfter(): ?bool
  {
      return $this->insertPageBreakAfter;
  }

  public function setInsertPageBreakAfter(?bool $insertPageBreakAfter): static
  {
      $this->insertPageBreakAfter = $insertPageBreakAfter;

      return $this;
  }

}
