<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Tests\Unit\Services\Matching;

use ksfraser\FrontAccounting\ImportStaging\Services\Matching\Similarity;
use PHPUnit\Framework\TestCase;

/**
 * @package ksf_FA_ImportStagingProcessing
 */
class SimilarityTest extends TestCase
{
    /** @var Similarity */
    private $similarity;

    protected function setUp(): void
    {
        parent::setUp();

        $this->similarity = new Similarity();
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public function soundexEqualProvider(): array
    {
        return array(
            'spelling variant' => array('Lovelace', 'Lovalace'),
            'classic smith/smyth' => array('Smith', 'Smyth'),
            'common variant' => array('Kowalski', 'Kowalsky'),
            'case insensitive' => array('LOVELACE', 'lovelace'),
            'punctuation' => array("O'Brien", 'OBrien'),
        );
    }

    /**
     * @dataProvider soundexEqualProvider
     */
    public function testSoundexFoldsSpellingVariants(string $a, string $b): void
    {
        $this->assertTrue(
            $this->similarity->soundexMatches($a, $b),
            "expected '{$a}' and '{$b}' to share a soundex code"
        );
    }

    public function testSoundexSeparatesDifferentNames(): void
    {
        $this->assertFalse($this->similarity->soundexMatches('Smith', 'Jones'));
        $this->assertFalse($this->similarity->soundexMatches('Lovelace', 'Turing'));
    }

    /**
     * H and W are transparent in soundex; they must not consume a code slot.
     */
    public function testSoundexKnownCodes(): void
    {
        $this->assertSame('L142', $this->similarity->soundex('Lovelace'));
        $this->assertSame('S530', $this->similarity->soundex('Smith'));
        $this->assertSame('J520', $this->similarity->soundex('Jones'));
        $this->assertSame('T652', $this->similarity->soundex('Turing'));
    }

    /**
     * Common short names collide on soundex; the code must not certify a match.
     */
    public function testUncodeableInputYieldsEmptyCode(): void
    {
        $this->assertSame('', $this->similarity->soundex('123'));
        $this->assertSame('', $this->similarity->soundex(''));
        $this->assertFalse($this->similarity->soundexMatches('', 'Lovelace'));
    }

    public function testNameSimilarityIsBounded(): void
    {
        $this->assertSame(1.0, $this->similarity->nameSimilarity('Lovelace', 'lovelace'));
        $this->assertSame(0.0, $this->similarity->nameSimilarity('', 'Lovelace'));
        $this->assertSame(0.0, $this->similarity->nameSimilarity('Lovelace', ''));
    }

    /**
     * A soundex collision lifts the score but stops short of 1.0, so "Lee" and
     * "Leigh" cannot be treated as the same person.
     */
    public function testSoundexCollisionDoesNotCertifyIdentity(): void
    {
        $similarity = $this->similarity->nameSimilarity('Lee', 'Leigh');

        $this->assertLessThan(1.0, $similarity);
        $this->assertGreaterThan(0.0, $similarity);
    }

    public function testRatioIsBounded(): void
    {
        $this->assertSame(1.0, $this->similarity->ratio('abc', 'abc'));
        $this->assertSame(0.0, $this->similarity->ratio('abc', ''));
        $this->assertGreaterThanOrEqual(0.0, $this->similarity->ratio('kitten', 'sitting'));
        $this->assertLessThanOrEqual(1.0, $this->similarity->ratio('kitten', 'sitting'));
    }

    public function testSurnameAndFirstNameExtraction(): void
    {
        $this->assertSame('Lovelace', $this->similarity->surname('Ada Lovelace'));
        $this->assertSame('Ada', $this->similarity->firstName('Ada Lovelace'));

        // Single-token names: the token is both.
        $this->assertSame('Prince', $this->similarity->surname('Prince'));
        $this->assertSame('Prince', $this->similarity->firstName('Prince'));

        $this->assertSame('', $this->similarity->surname(''));
    }

    public function testNormalisationStripsPunctuationAndCollapsesSpaces(): void
    {
        $this->assertSame(
            $this->similarity->soundex('Ada   Byron-King'),
            $this->similarity->soundex('adabryonking')
        );
    }

    public function testLevenshteinHandlesLongInputs(): void
    {
        $a = str_repeat('abcdefghij', 20);
        $b = $a . 'k';

        $this->assertGreaterThan(0.95, $this->similarity->ratio($a, $b));
    }
}