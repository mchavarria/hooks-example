<?php
declare(strict_types=1);

namespace Mch\App\GitHooks;

use CaptainHook\App\Config;
use CaptainHook\App\Config\Action;
use CaptainHook\App\Console\IO;
use CaptainHook\App\Exception\ActionFailed;
use CaptainHook\App\Hook\Action as HookAction;
use SebastianFeldmann\Git\Repository as Repo;

class ValidateBranchName implements HookAction
{

    private const PERMANENT_BRANCHES = ['master', 'dev', 'release'];
    private const BRANCH_REGEX_W_OPTIONS = '^%s\/%s@%s\w+';
    private const BRANCH_REGEX_DEFAULT = '/^[a-zA-Z]{2,7}\\/[A-Z]{3,}-[0-9]{1,4}@[A-Z]{2,}\\w+/';


    public function execute(Config $config, IO $io, Repo $repository, Action $action): void
    {
        $branch = $repository->getInfoOperator()->getCurrentBranch();
        // Skip on permanent branches. Continue only for temporal branches
        if (in_array($branch, self::PERMANENT_BRANCHES, true) === true) {
            return;
        }

        // If set, use the regex with the given options
        $options = $action->getOptions();
        if ($options->get('branches') !== null
            && $options->get('teams') !== null
            && $options->get('projects') !== null
        ) {
            $regex = sprintf(
                self::BRANCH_REGEX_W_OPTIONS,
                sprintf('(%s)', implode('|', $options->get('branches'))),
                sprintf('(%s)', implode('|', $options->get('teams'))),
                sprintf('(%s)', implode('|', $options->get('projects')))
            );

            $this->validateBranchName($branch, $regex);

            return;
        }

        // If not, use the generic regex
        $this->validateBranchName($branch, self::BRANCH_REGEX_DEFAULT);
    }

    /**
     * @throws ActionFailed
     */
    private function validateBranchName(string $branch, string $regex): void
    {
        if (!preg_match($regex, $branch, $matches)) {
            throw new ActionFailed('Incorrect branch name');
        }
    }
}