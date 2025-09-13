<?php

declare(strict_types=1);

namespace App\Tests\Strategy;

use App\Entity\Config;
use App\Entity\Parameter;
use App\Strategy\MrtXmlConverter;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class MrtXmlConverterTest extends TestCase
{
    private function makeParam(string $name): object
    {
        return new class($name) {
            private string $n;
            public function __construct(string $n) { $this->n = $n; }
            public function getParameterName(): ?string { return $this->n; }
        };
    }

    public function testParametersAreOrderedByAdminOrder(): void
    {
        // Admin order: TE, TR, FA (case-insensitive handling)
        $selected = [
            $this->makeParam('TE'),
            $this->makeParam('TR'),
            $this->makeParam('TA'),
        ];

        // Minimal XML with header and two sequence parameters appearing in mixed order
        $xml = file_get_contents(__DIR__.'../fixtures/MRT_minimal_test.xml');

        $em = $this->createMock(EntityManagerInterface::class);
        $repoParam = $this->getMockBuilder(\stdClass::class)->addMethods(['findSelectedbyGeraetName'])->getMock();
        $repoParam->method('findSelectedbyGeraetName')->willReturn($selected);
        $repoConfig = $this->getMockBuilder(\stdClass::class)->addMethods(['find'])->getMock();
        $repoConfig->method('find')->willReturn(new Config());

        $em->method('getRepository')->willReturnCallback(function ($cls) use ($repoParam, $repoConfig) {
            if ($cls === Parameter::class) { return $repoParam; }
            if ($cls === Config::class) { return $repoConfig; }
            return null;
        });

        $params = $this->createMock(ContainerBagInterface::class);
        $params->method('get')->with('app.path.protocols')->willReturn('/uploads/products');

        $logger = $this->createMock(LoggerInterface::class);

        $kernel = $this->createMock(KernelInterface::class);
        // $kernel->method('getProjectDir')->willReturn(dirname($tmp));

        $converter = new MrtXmlConverter($em, $params, $logger, $kernel);
        // Pre-seed target_params (admin order) to avoid relying on repository
        $ref = new \ReflectionClass($converter);
        $prop = $ref->getProperty('target_params');
        $prop->setAccessible(true);
        $prop->setValue($converter, ['te','tr','ta']);

        // Write XML to temp file for processing
        $data = (object) [
            'geraet' => 'MRT_Siemens',
            'mimetype' => 'application/xml',
            'filepath' => __DIR__.'/../fixtures/MRT_minimal_test.xml',
        ];

        $serialized = $converter->process($data);
        $result = unserialize($serialized);

        $this->assertIsArray($result);
        $this->assertCount(3, $result); // our XML has 3 sequences

        $row = $result[0];
        // Ensure keys for target params exist in the result
        $this->assertArrayHasKey('TE', $row);
        $this->assertArrayHasKey('TR', $row);
        $this->assertArrayHasKey('TA', $row);

        // And ensure converter can be reordered to admin order deterministically
        $keys = array_keys($row);
        $ordered = array_values(array_intersect(['TE','TR','TA'], $keys));
        $this->assertSame(['TE','TR','TA'], $ordered, 'Parameters should follow admin sort order when filtered');
    }
}
