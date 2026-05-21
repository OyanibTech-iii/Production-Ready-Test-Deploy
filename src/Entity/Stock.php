<?php

namespace App\Entity;

use App\Repository\StockRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Put(),
        new Delete()
    ],
    normalizationContext: [
        'groups' => ['stock:read']
    ],
    denormalizationContext: [
        'groups' => ['stock:write']
    ]
)]


#[ORM\Entity(repositoryClass: StockRepository::class)]
class Stock
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['stock:read', 'stock:write'])]
    private ?int $id = null;

    #[ORM\Column]
    #[Groups(['stock:read', 'stock:write'])]

    private ?int $Quantity = null;

    // ADD THESE NEW FIELDS:
    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['stock:read', 'stock:write'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['stock:read', 'stock:write'])]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['stock:read', 'stock:write'])]
    private ?string $stockType = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['stock:read', 'stock:write'])]
    private ?int $minimumQuantity = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['stock:read', 'stock:write'])]
    private ?int $maximumQuantity = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    #[Groups(['stock:read', 'stock:write'])]
    private ?User $owner = null;

    /**
     * @var Collection<int, Product>
     */
    #[ORM\ManyToMany(targetEntity: Product::class, inversedBy: 'stocks')]
    #[ORM\JoinTable(name: 'stock_product')]
    #[Groups(['stock:read', 'stock:write'])]
    private Collection $products;

    #[ORM\ManyToOne(inversedBy: 'stocks')]
    #[ORM\JoinColumn(name: 'location_rel_id', referencedColumnName: 'id',nullable: true)]
    #[Groups(['stock:read', 'stock:write'])]
    private ?Location $location = null;

    public function __construct()
    {
        $this->products = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuantity(): ?int
    {
        return $this->Quantity;
    }

    public function setQuantity(int $Quantity): static
    {
        $this->Quantity = $Quantity;
        return $this;
    }

    /**
     * @return Collection<int, Product>
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(Product $product): static
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->addStock($this);
        }
        return $this;
    }

    public function removeProduct(Product $product): static
    {
        if ($this->products->removeElement($product)) {
            $product->removeStock($this);
        }
        return $this;
    }
    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getStockType(): ?string
    {
        return $this->stockType;
    }

    public function setStockType(?string $stockType): static
    {
        $this->stockType = $stockType;
        return $this;
    }

    public function getMinimumQuantity(): ?int
    {
        return $this->minimumQuantity;
    }

    public function setMinimumQuantity(?int $minimumQuantity): static
    {
        $this->minimumQuantity = $minimumQuantity;
        return $this;
    }

    public function getMaximumQuantity(): ?int
    {
        return $this->maximumQuantity;
    }

    public function setMaximumQuantity(?int $maximumQuantity): static
    {
        $this->maximumQuantity = $maximumQuantity;
        return $this;
    }

    /**
     * Check if stock is low (below minimum quantity)
     */
    public function isLowStock(): bool
    {
        if (!$this->minimumQuantity) {
            return false;
        }

        return $this->Quantity <= $this->minimumQuantity;
    }

    /**
     * Check if stock is at maximum capacity
     */
    public function isAtMaxCapacity(): bool
    {
        if (!$this->maximumQuantity) {
            return false;
        }

        return $this->Quantity >= $this->maximumQuantity;
    }

    /**
     * Get stock status
     */
    public function getStatus(): string
    {
        if ($this->Quantity === 0) {
            return 'Out of stock';
        }

        if ($this->isLowStock()) {
            return 'Low stock';
        }

        if ($this->isAtMaxCapacity()) {
            return 'At capacity';
        }

        return 'In stock';
    }

    /**
     * Get the number of products using this stock
     */
    public function getProductCount(): int
    {
        return $this->products->count();
    }

    /**
     * Get a summary of products using this stock
     */
    public function getProductSummary(): string
    {
        $count = $this->getProductCount();

        if ($count === 0) {
            return 'No products assigned';
        }

        if ($count === 1) {
            return '1 product assigned';
        }

        return $count . ' products assigned';
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;
        return $this;
    }

    public function isOwnedBy(?User $user): bool
    {
        return $user && $this->owner && $this->owner->getId() === $user->getId();
    }

    public function getLocationRel(): ?Location
    {
        return $this->location;
    }

    public function setLocationRel(?Location $location): static
    {
        $this->location = $location;

        return $this;
    }
}