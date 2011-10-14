<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Liip\ImagineBundle\Tests\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Liip\ImagineBundle\DependencyInjection\LiipImagineExtension;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\DependencyInjection\Reference;

class LiipImagineExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerBuilder
     */
    protected $containerBuilder;

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    public function testUserLoadThrowsExceptionUnlessDriverIsValid()
    {
        $loader = new LiipImagineExtension();
        $config = array('driver' => 'foo');
        $loader->load(array($config), new ContainerBuilder());
    }

    public function testLoadWithDefaults()
    {
        $this->createEmptyConfiguration();

        $this->assertParameter(true, 'liip_imagine.cache');
        $this->assertAlias('liip_imagine.gd', 'liip_imagine');
        $this->assertHasDefinition('liip_imagine.controller');
        $this->assertDICConstructorArguments(
            $this->containerBuilder->getDefinition('liip_imagine.controller'),
            array(new Reference('liip_imagine.loader.filesystem'), new Reference('liip_imagine.filter.manager'), new Reference('liip_imagine.cache.path.resolver'))
        );
    }

    public function testLoad()
    {
        $this->createFullConfiguration();

        $this->assertParameter(false, 'liip_imagine.cache');
        $this->assertAlias('liip_imagine.imagick', 'liip_imagine');
        $this->assertHasDefinition('liip_imagine.controller');
        $this->assertDICConstructorArguments(
            $this->containerBuilder->getDefinition('liip_imagine.controller'),
            array(new Reference('acme_imagine.loader'), new Reference('liip_imagine.filter.manager'))
        );
    }

    /**
     * @return ContainerBuilder
     */
    protected function createEmptyConfiguration()
    {
        $this->containerBuilder = new ContainerBuilder();
        $loader = new LiipImagineExtension();
        $loader->load(array(array()), $this->containerBuilder);
        $this->assertTrue($this->containerBuilder instanceof ContainerBuilder);
    }

    /**
     * @return ContainerBuilder
     */
    protected function createFullConfiguration()
    {
        $this->containerBuilder = new ContainerBuilder();
        $loader = new LiipImagineExtension();
        $loader->load(array($this->getFullConfig()), $this->containerBuilder);
        $this->assertTrue($this->containerBuilder instanceof ContainerBuilder);
    }

    protected function getFullConfig()
    {
        $yaml = <<<EOF
driver: imagick
web_root: ../foo/bar
cache_prefix: /imagine/cache
cache: false
formats: ['json', 'xml', 'jpg', 'png', 'gif']
filters:
    small:
        type:    thumbnail
        options: { size: [100, ~], mode: inset, quality: 80 }
    medium_small_cropped:
        type:    thumbnail
        options: { size: [223, 173], mode: outbound, quality: 80 }
    medium_cropped:
        type:    thumbnail
        options: { size: [232, 180], mode: outbound, quality: 80 }
    medium:
        type:    thumbnail
        options: { size: [232, 180], mode: inset, quality: 80 }
    large_cropped:
        type:    thumbnail
        options: { size: [483, 350], mode: outbound, quality: 100 }
    large:
        type:    thumbnail
        options: { size: [483, ~], mode: inset, quality: 100 }
    xxl:
        type:    thumbnail
        options: { size: [660, ~], mode: inset, quality: 100 }
    '':
        type: ~
        options: { quality: 100 }
loader: acme_imagine.loader
EOF;
        $parser = new Parser();

        return $parser->parse($yaml);
    }

    private function assertAlias($value, $key)
    {
        $this->assertEquals($value, (string) $this->containerBuilder->getAlias($key), sprintf('%s alias is correct', $key));
    }

    private function assertParameter($value, $key)
    {
        $this->assertEquals($value, $this->containerBuilder->getParameter($key), sprintf('%s parameter is correct', $key));
    }

    private function assertHasDefinition($id)
    {
        $this->assertTrue(($this->containerBuilder->hasDefinition($id) ?: $this->containerBuilder->hasAlias($id)));
    }

    private function assertNotHasDefinition($id)
    {
        $this->assertFalse(($this->containerBuilder->hasDefinition($id) ?: $this->containerBuilder->hasAlias($id)));
    }

    private function assertDICConstructorArguments($definition, $args)
    {
        $this->assertEquals($args, $definition->getArguments(), "Expected and actual DIC Service constructor arguments of definition '".$definition->getClass()."' don't match.");
    }

    protected function tearDown()
    {
        unset($this->containerBuilder);
    }
}
