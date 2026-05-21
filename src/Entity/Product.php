<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
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
        'groups' => ['product:read']
    ],
    denormalizationContext: [
        'groups' => ['product:write']
    ]
)]

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['product:read', 'product:write'])]

    private ?int $id = null;

    #[ORM\Column(length: 200)]
    #[Groups(['product:read', 'product:write'])]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Groups(['product:read', 'product:write'])]

    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['product:read', 'product:write'])]
    private ?string $price = null;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['product:read', 'product:write'])]
    private ?bool $isAvailable = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['product:read', 'product:write'])]

    private ?string $image = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    #[Groups(['product:read', 'product:write'])]

    private ?User $owner = null;

    /**
     * @var Collection<int, Stock>
     */
    #[ORM\ManyToMany(targetEntity: Stock::class, mappedBy: 'products')]
    private Collection $stocks;

    /**
     * @var Collection<int, Review>
     */
    #[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'product')]
    private Collection $reviews;

    public function __construct()
    {
        $this->stocks = new ArrayCollection();
        $this->reviews = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function isAvailable(): ?bool
    {
        return $this->isAvailable;
    }

    public function setIsAvailable(bool $isAvailable): static
    {
        $this->isAvailable = $isAvailable;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
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

    /**
     * Return a web-accessible path for the product image or an absolute URL.
     * - If the stored image value is an absolute URL (http/https) it is returned as-is.
     * - If it's a filename, the method returns the relative path inside public/ (uploads/products/).
     */
    public function getImagePath(): ?string
    {
        if (null === $this->image || '' === $this->image) {
            return null;
        }

        // If the image value is already an absolute URL or starts with /, return it unchanged
        if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://') || str_starts_with($this->image, '/')) {
            return $this->image;
        }

        // Otherwise assume it's a filename stored in public/uploads/images/
        return 'uploads/images/' . $this->image;
    }

    /**
     * @return Collection<int, Stock>
     */
    public function getStocks(): Collection
    {
        return $this->stocks;
    }

    public function addStock(Stock $stock): static
    {
        if (!$this->stocks->contains($stock)) {
            $this->stocks->add($stock);
            $stock->addProduct($this);
        }
        return $this;
    }

    public function removeStock(Stock $stock): static
    {
        if ($this->stocks->removeElement($stock)) {
            $stock->removeProduct($this);
        }
        return $this;
    }

    /**
     * Total quantity across all linked stock records.
     */
    public function getCurrentStockQuantity(): int
    {
        $total = 0;
        foreach ($this->stocks as $stock) {
            $total += $stock->getQuantity() ?? 0;
        }
        return $total;
    }

    /**
     * Decrement stock by the given quantity across linked stock records (FIFO).
     * Caller must ensure quantity <= getCurrentStockQuantity().
     *
     * @return bool True if decrement succeeded
     */
    public function decrementStock(int $quantity): bool
    {
        if ($quantity <= 0) {
            return true;
        }
        $remaining = $quantity;
        foreach ($this->stocks as $stock) {
            if ($remaining <= 0) {
                break;
            }
            $stockQty = $stock->getQuantity() ?? 0;
            if ($stockQty <= 0) {
                continue;
            }
            $deduct = min($stockQty, $remaining);
            $stock->setQuantity($stockQty - $deduct);
            $stock->setUpdatedAt(new \DateTime());
            $remaining -= $deduct;
        }
        return $remaining <= 0;
    }

    /**
     * Increment stock by the given quantity.
     */
    public function incrementStock(int $quantity): bool
    {
        if ($quantity <= 0) {
            return true;
        }

        foreach ($this->stocks as $stock) {
            $stockQty = $stock->getQuantity() ?? 0;
            $stock->setQuantity($stockQty + $quantity);
            $stock->setUpdatedAt(new \DateTime());
            return true;
        }

        return false;
    }

    /**
     * Simplified stock status derived from total quantity.
     */
    public function getStockStatus(): string
    {
        $qty = $this->getCurrentStockQuantity();
        if ($qty <= 0) {
            return 'Out of stock';
        }

        if ($qty <= 5) {
            return 'Low stock';
        }

        return 'In stock';
    }

    /**
     * @return Collection<int, Review>
     */
    public function getReviews(): Collection
    {
        return $this->reviews;
    }


    public function addReview(Review $review): static
    {
        if (!$this->reviews->contains($review)) {
            $this->reviews->add($review);
            $review->setProduct($this);
        }

        return $this;
    }

    public function removeReview(Review $review): static
    {
        if ($this->reviews->removeElement($review)) {
            // set the owning side to null (unless already changed)
            if ($review->getProduct() === $this) {
                $review->setProduct(null);
            }
        }

        return $this;
    }
    public function getAverageRating(): float
    {
        if ($this->reviews->isEmpty()) {
            return 0.0;
        }

        $total = 0;
        foreach ($this->reviews as $review) {
            $total += $review->getRating();
        }

        return round($total / $this->reviews->count(), 1);
    }
}