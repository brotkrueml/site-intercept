<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Extractor\Factory;

use App\Entity\DocumentationJar;
use App\Exception\ComposerJsonInvalidException;
use App\Exception\DocsPackageDoNotCareBranch;
use App\Extractor\ComposerJson;
use App\Extractor\DeploymentInformation;
use App\Extractor\PushEvent;

class DeploymentInformationFactory
{
    /**
     * @var array
     */
    private static $typeMap = [
        'typo3-cms-documentation' => ['m' => 'manual'],
        'typo3-cms-framework' => ['c' => 'core-extension'],
        'typo3-cms-extension' => ['p' => 'extension'],
        // There is a third one 'h' => 'docs-home', handled below.
    ];

    /**
     * @param ComposerJson $composerJson
     * @param PushEvent $pushEvent
     * @param string $privateDir
     * @param string $subDir
     * @return DeploymentInformation
     * @throws ComposerJsonInvalidException
     * @throws DocsPackageDoNotCareBranch
     */
    public function buildFromComposerJson(ComposerJson $composerJson, PushEvent $pushEvent, string $privateDir, string $subDir): DeploymentInformation
    {
        $repositoryUrl = $pushEvent->getRepositoryUrl();
        $packageName = $this->determinePackageName($composerJson);
        $packageType = $this->determinePackageType($composerJson, $repositoryUrl);

        $vendor = key($packageName);
        $name = current($packageName);
        $typeLong = current($packageType);
        $typeShort = key($packageType);
        $sourceBranch = $pushEvent->getVersionString();

        return new DeploymentInformation(
            $repositoryUrl,
            $vendor,
            $name,
            $typeLong,
            $typeShort,
            $sourceBranch,
            $privateDir,
            $subDir
        );
    }

    /**
     * @param DocumentationJar $documentationJar
     * @param string $privateDir
     * @param string $subDir
     * @return DeploymentInformation
     * @throws DocsPackageDoNotCareBranch
     */
    public function buildFromDocumentationJar(DocumentationJar $documentationJar, string $privateDir, string $subDir): DeploymentInformation
    {
        $repositoryUrl = $documentationJar->getRepositoryUrl();
        $packageName = $documentationJar->getPackageName();

        $packageDetails = explode('/', $packageName);
        $vendor = $packageDetails[0];
        $name = $packageDetails[1];
        $typeLong = $documentationJar->getTypeLong();
        $typeShort = $documentationJar->getTypeShort();
        $sourceBranch = $documentationJar->getBranch();

        return new DeploymentInformation(
            $repositoryUrl,
            $vendor,
            $name,
            $typeLong,
            $typeShort,
            $sourceBranch,
            $privateDir,
            $subDir
        );
    }

    /**
     * @param ComposerJson $composerJson
     * @param string $repositoryUrl
     * @return array
     * @throws ComposerJsonInvalidException
     */
    private function determinePackageType(ComposerJson $composerJson, string $repositoryUrl): array
    {
        if ($repositoryUrl === 'https://github.com/TYPO3-Documentation/DocsTypo3Org-Homepage.git') {
            // Hard coded final location for the docs homepage repository
            return [
                'h' => 'docs-home',
            ];
        }

        if (!array_key_exists($composerJson->getType(), self::$typeMap)) {
            throw new ComposerJsonInvalidException('composer.json \'type\' must be set to one of ' . implode(', ', array_keys(self::$typeMap)) . '.', 1557490474);
        }

        return self::$typeMap[$composerJson->getType()];
    }

    /**
     * @param ComposerJson $composerJson
     * @return array
     * @throws ComposerJsonInvalidException
     */
    private function determinePackageName(ComposerJson $composerJson): array
    {
        if (!preg_match('/^[\w-]+\/[\w-]+$/', $composerJson->getName())) {
            throw new ComposerJsonInvalidException('composer.json \'name\' must be of form \'vendor/package\', \'' . $composerJson->getName() . '\' given.', 1553082490);
        }

        [$vendor, $name] = explode('/', $composerJson->getName());
        return [$vendor => $name];
    }
}
