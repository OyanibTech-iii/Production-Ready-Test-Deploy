<?php

namespace App\DataFixtures;

use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ProductFixtures extends Fixture implements DependentFixtureInterface
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Ensure the official owner exists
        $ownerEmail = 'growficoofficial@gmail.com';
        $owner = $manager->getRepository(User::class)->findOneBy(['email' => $ownerEmail]);

        if (!$owner) {
            $owner = new User();
            $owner->setEmail($ownerEmail);
            $owner->setFirstName('Growfico official');
            $owner->setLastName('Administrator');
            $owner->setRoles(['ROLE_ADMIN']);
            $owner->setIsVerified(true);
            $owner->setIsActive(true);
            $owner->setPassword($this->passwordHasher->hashPassword($owner, 'growficoofficial@gmail.com'));
            $manager->persist($owner);
        }

        // Data from backupdb.sql (IDs removed so they start from 1 in a clean DB)
        $productsData = [
            ['Bread fruit', 'Rich in carbohydrates', 280.00, 1, 'bfs-69d0c5dd0afdc.png'],
            ['Black Sapote', 'Chocolate taste fruit', 250.00, 1, 'blk_spt-69d0c635738c8.png'],
            ['Cashew', 'Wine production', 250.00, 1, 'ksy-69d0c66a739e2.png'],
            ['Kamias', 'Rare sour fruit', 300.00, 1, 'kamias-69d0c69a0890a.png'],
            ['Mansanitas', 'Decoration rich in fruits', 250.00, 1, 'mansantas-69d0c6cea8ed5.png'],
            ['Señiorita', 'Sweet and rich in fruits', 300.00, 1, 'sen-69d0c72dba1c3.png'],
            ['Mabolo', 'Rare native fruit', 400.00, 1, 'mblo-69d0c75eb5ccb.png'],
            ['Spiral', 'Decoration', 440.00, 1, 'sprial-69ca0d01c8271-69d0c7d101d88.png'],
            ['Duranta', 'Also known as yellow tops', 400.00, 1, 'duranta-69b8e8d4d07ef-69d0c80022241.png'],
            ['Fukien Tea', 'Landscaping essential', 480.00, 1, 'fukien_tea-69b8e732a09c4-69d0c84e409f4.png'],
            ['Privet', 'Home display', 420.00, 1, 'privet-69b8e80542065-69d0c88e633aa.png'],
            ['Murraya', 'Pathway decoration', 470.00, 1, 'murraya-69b8ea27c9ee6-69d0c8c47a927.png'],
            ['Aggregate', 'Base for bonsai and pathway', 200.00, 1, 'AGS-69ca0829750da-69d0c924b7e71.png'],
            ['Black Coal Sand', 'Mixing to base soil', 200.00, 1, 'BCS-69d0c98707a5e.png'],
            ['Cone', 'Cone shape ficus', 400.00, 1, 'cone-69d0c9c912e15.png'],
            ['Box  Murraya', 'Box shape murraya', 300.00, 1, 'box-69d0ca08842e9.png'],
            ['Carbonize Rice Hull', 'Use to basal fertilizer', 150.00, 1, 'CRH-69d0cabb18875.png'],
            ['Lamp', 'Lamp shape', 400.00, 1, 'lamp-69d0cadadaf29.png'],
            ['Vermi Cast', 'Rich fertilizer in nitrogen', 300.00, 1, 'vmi-69d0cb1099142.png'],
            ['Canistel', 'Egg fruit', 400.00, 1, 'cntl-69d0cb35e7798.png'],
            ['Limestones', 'Pathway steps', 100.00, 1, 'lime-69d0d3de8df9f.png'],
            ['Sandstone', 'Pathway stepping stone', 100.00, 1, 'clay-69d0d4d215486.png'],
            ['Slate', 'Natural cleft  slate', 200.00, 1, 'rock-69d0d511664ef.png'],
            ['Sand Stone', 'Green slate sandstone', 100.00, 1, 'sandpaper-69d0d555b1df9.png'],
            ['Basalt', 'Round basalt slate', 100.00, 1, 'circular-69d0d599ba38d.png'],
            ['Granite', 'Bluestone granite step', 100.00, 1, 'dotted-69d0d5fcc0ace.png'],
            ['Red Clay Bricks', 'Classic paving bricks', 200.00, 1, '1-69d0d6b3799e3.png'],
            ['Concrete Paver', 'Double walkway block', 20.00, 1, '2-69d0d6ee3ae17.png'],
            ['Biege Sandstone', 'Warm garden paver', 20.00, 1, '4-69d0d7ca381f8.png'],
            ['Black Basalt', 'Modern stone block', 20.00, 1, '3-69d0d750e2b5c.png'],
            ['Basalt Paver', 'Modern stone paver', 20.00, 1, '4-69d0d7a59eb7a.png'],
            ['Rustic Patio', 'Brown brick  paver', 20.00, 1, '5-69d0d8192c2c6.png'],
            ['Cobblestone', 'Natural driveway stone', 20.00, 1, '6-69d0d847dd107.png'],
            ['Zoysia', 'Dense turf grass', 140.00, 1, 'zoysia-69d0d8acf12f5.png'],
            ['Carpet', 'Low ground cover', 140.00, 1, 'carpet-69d0d8e456b8b.png'],
            ['Centipede', 'Low maintenance lawn', 140.00, 1, 'centipede-69d0d91de528d.png'],
            ['Buffalo', 'Drought tolerant turf', 140.00, 1, 'buffalo-69d0d94f14cc9.png'],
            ['Saw Dust', 'Basal soil mixture', 180.00, 1, 'swdt-69d0da525fa82.png'],
            ['Cocopit', 'Basal and rooting medium', 280.00, 1, 'CCPT-69d0da7bbc4b5.png'],
            ['River sand', 'Base for loose rooted plants', 180.00, 1, 'sand-69d0dae584762.png'],
        ];

        foreach ($productsData as $data) {
            $product = $manager->getRepository(Product::class)->findOneBy(['name' => $data[0]]) ?? new Product();
            $product->setName($data[0]);
            $product->setDescription($data[1]);
            $product->setPrice((string)$data[2]);
            $product->setIsAvailable((bool)$data[3]);
            $product->setImage('/uploads/images/' . $data[4]);
            $product->setOwner($owner);

            $manager->persist($product);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixture::class,
            AdminFixture::class,
        ];
    }
}
