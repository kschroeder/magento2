<?php

/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Setup\Test\Unit\Module\Di\Code\Reader;

use Magento\Setup\Module\Di\Code\Reader\FileClassScanner;
use Magento\Setup\Module\Di\Code\Reader\InvalidFileException;

class FileClassScannerTest extends \PHPUnit_Framework_TestCase
{

    public function testInvalidFileThrowsException()
    {
        $this->setExpectedException(InvalidFileException::class);
        new FileClassScanner(false);
    }

    public function testEmptyArrayForFileWithoutNamespaceOrClass()
    {
        $scanner = $this->getMockBuilder(FileClassScanner::class)->disableOriginalConstructor()->setMethods([
            'getFileContents'
        ])->getMock();
        $scanner->expects(self::once())->method('getFileContents')->willReturn(
            <<<PHP
<?php

echo 'hello world';

if (class_exists('some_class')) {
    \$object = new some_class();
}
PHP
        );
        /** @var $scanner FileClassScanner */

        $result = $scanner->getClassNames();
        self::assertCount(0, $result);
    }

    public function testGetClassName()
    {
        $scanner = $this->getMockBuilder(FileClassScanner::class)->disableOriginalConstructor()->setMethods([
            'getFileContents'
        ])->getMock();
        $scanner->expects(self::once())->method('getFileContents')->willReturn(
            <<<PHP
<?php

class ThisIsATest {

}
PHP
        );
        /** @var $scanner FileClassScanner */

        $result = $scanner->getClassNames();

        self::assertCount(1, $result);
        self::assertContains('ThisIsATest', $result);
    }

    public function testGetClassNameAndSingleNamespace()
    {
        $scanner = $this->getMockBuilder(FileClassScanner::class)->disableOriginalConstructor()->setMethods([
            'getFileContents'
        ])->getMock();
        $scanner->expects(self::once())->method('getFileContents')->willReturn(
            <<<PHP
<?php

namespace NS;

class ThisIsMyTest {

}
PHP
        );
        /** @var $scanner FileClassScanner */

        $result = $scanner->getClassNames();

        self::assertCount(1, $result);
        self::assertContains('NS\ThisIsMyTest', $result);
    }

    public function testGetClassNameAndMultiNamespace()
    {
        $scanner = $this->getMockBuilder(FileClassScanner::class)->disableOriginalConstructor()->setMethods([
            'getFileContents'
        ])->getMock();
        $scanner->expects(self::once())->method('getFileContents')->willReturn(
            <<<PHP
<?php

namespace This\Is\My\Ns;

class ThisIsMyTest {

    public function __construct()
    {
        \This\Is\Another\Ns::class;
    }
    
    public function test()
    {
        
    }
}
PHP
        );
        /** @var $scanner FileClassScanner */

        $result = $scanner->getClassNames();

        self::assertCount(1, $result);
        self::assertContains('This\Is\My\Ns\ThisIsMyTest', $result);
    }

    public function testGetMultiClassNameAndMultiNamespace()
    {
        $scanner = $this->getMockBuilder(FileClassScanner::class)->disableOriginalConstructor()->setMethods([
            'getFileContents'
        ])->getMock();
        $scanner->expects(self::once())->method('getFileContents')->willReturn(
            <<<PHP
<?php

namespace This\Is\My\Ns;

class ThisIsMyTest {

    public function __construct()
    {
        \$this->get(\This\Is\Another\Ns::class)->method();
        self:: class;
    }
    
    public function test()
    {
        
    }
}

class ThisIsForBreaking {

}

PHP
        );
        /** @var $scanner FileClassScanner */

        $result = $scanner->getClassNames();

        self::assertCount(2, $result);
        self::assertContains('This\Is\My\Ns\ThisIsMyTest', $result);
        self::assertContains('This\Is\My\Ns\ThisIsForBreaking', $result);
    }

    public function testBracketedNamespacesAndClasses()
    {
        $scanner = $this->getMockBuilder(FileClassScanner::class)->disableOriginalConstructor()->setMethods([
            'getFileContents'
        ])->getMock();
        $scanner->expects(self::once())->method('getFileContents')->willReturn(
            <<<PHP
<?php

namespace This\Is\My\Ns {

    class ThisIsMyTest
    {
    
        public function __construct()
        {
            \This\Is\Another\Ns::class;
            self:: class;
        }
    
    }
    
    class ThisIsForBreaking
    {
    }
}

namespace This\Is\Not\My\Ns {

    class ThisIsNotMyTest
    {
    }   
}

PHP
        );
        /** @var $scanner FileClassScanner */

        $result = $scanner->getClassNames();

        self::assertCount(3, $result);
        self::assertContains('This\Is\My\Ns\ThisIsMyTest', $result);
        self::assertContains('This\Is\My\Ns\ThisIsForBreaking', $result);
        self::assertContains('This\Is\Not\My\Ns\ThisIsNotMyTest', $result);
    }

    public function testClassKeywordInMiddleOfFile()
    {
        $filename = __DIR__
            . '/../../../../../../../../../..'
            . '/app/code/Magento/Catalog/Model/ResourceModel/Product/Indexer/Eav/AbstractEav.php';
        $filename = realpath($filename);
        $scanner = new FileClassScanner($filename);
        $result = $scanner->getClassNames();

        self::assertCount(1, $result);
    }

    public function testInvalidPHPCodeThrowsExceptionWhenCannotDetermineBraceOrSemiColon()
    {
        $this->setExpectedException(InvalidFileException::class);
        $scanner = $this->getMockBuilder(FileClassScanner::class)->disableOriginalConstructor()->setMethods([
            'getFileContents'
        ])->getMock();
        $scanner->expects(self::once())->method('getFileContents')->willReturn(
            <<<PHP
            <?php

namespace This\Is\My\Ns 

class ThisIsMyTest
{
}

PHP
        );
        /** @var $scanner FileClassScanner */

        $scanner->getClassNames();
    }
}
