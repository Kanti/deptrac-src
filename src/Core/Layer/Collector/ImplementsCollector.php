<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Core\Layer\Collector;

use LogicException;
use Qossmic\Deptrac\Contract\Ast\TokenReferenceInterface;
use Qossmic\Deptrac\Contract\Layer\CollectorInterface;
use Qossmic\Deptrac\Core\Analyser\AstMapExtractor;
use Qossmic\Deptrac\Core\Ast\AstMap\AstInheritType;
use Qossmic\Deptrac\Core\Ast\AstMap\AstMap;
use Qossmic\Deptrac\Core\Ast\AstMap\ClassLike\ClassLikeReference;
use Qossmic\Deptrac\Core\Ast\AstMap\ClassLike\ClassLikeToken;

final class ImplementsCollector implements CollectorInterface
{
    private readonly AstMap $astMap;

    public function __construct(private AstMapExtractor $astMapExtractor)
    {
        $this->astMap = $this->astMapExtractor->extract();
    }

    public function satisfy(array $config, TokenReferenceInterface $reference): bool
    {
        if (!$reference instanceof ClassLikeReference) {
            return false;
        }

        $interfaceName = $this->getInterfaceName($config);

        foreach ($this->astMap->getClassInherits($reference->getToken()) as $inherit) {
            if (AstInheritType::IMPLEMENTS === $inherit->type && $inherit->classLikeName->equals($interfaceName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, bool|string|array<string, string>> $config
     */
    private function getInterfaceName(array $config): ClassLikeToken
    {
        if (isset($config['implements']) && !isset($config['value'])) {
            trigger_deprecation('qossmic/deptrac', '0.20.0', 'ImplementsCollector should use the "value" key from this version');
            $config['value'] = $config['implements'];
        }

        if (!isset($config['value']) || !is_string($config['value'])) {
            throw new LogicException('ImplementsCollector needs the interface name as a string.');
        }

        return ClassLikeToken::fromFQCN($config['value']);
    }
}
