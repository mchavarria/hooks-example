<?php
declare(strict_types=1);

namespace Mch\App\GitHooks;

use CaptainHook\App\Config;
use CaptainHook\App\Config\Action;
use CaptainHook\App\Console\IO;
use CaptainHook\App\Exception\ActionFailed;
use CaptainHook\App\Hook\Action as HookAction;
use SebastianFeldmann\Git\CommitMessage;
use SebastianFeldmann\Git\Repository as Repo;

/**
 * Class PrepareCommitMessage
 *
 * Prepare git message based on the specs
 * @see https://dev-docu.int.power.cloud/guidelines/git/
 */
class PrepareCommitMessage implements HookAction
{
    private const DOC_URL = 'https://dev-docu.int.power.cloud/guidelines/git/';

    private const MSG_FORMAT = '[%s@%s] %s';
    private const BRANCH_MATCH_FORMAT = '/.%s@%s_.*/';

    private const TICKET_REGEX = '[A-Z]{2,}-[0-9]{1,}';
    private const PROJECT_ID_REGEX = '[a-zA-Z]{2,7}';

    public function execute(Config $config, IO $io, Repo $repository, Action $action): void
    {
        $oldMessage = $repository->getCommitMsg();
        $branch = $repository->getInfoOperator()->getCurrentBranch();

        $ticket = $this->extractTicketId($branch);
        $projectId = $this->extractProjectId($branch);

        $text = sprintf(self::MSG_FORMAT, $ticket, $projectId, $oldMessage->getContent());
        $text = trim(preg_replace('/\s\s+/', ' ', $text));
        $preparedMessage = new CommitMessage($text, $oldMessage->getCommentCharacter());

        $repository->setCommitMsg($preparedMessage);
    }

    /**
     * @throws ActionFailed
     */
    private function extractTicketId(string $branch): string
    {
        $regex = sprintf(
            self::BRANCH_MATCH_FORMAT,
            '(' . self::TICKET_REGEX . ')',
            self::PROJECT_ID_REGEX
        );

        preg_match($regex, $branch, $matches);
        if (isset($matches[1]) === false) {
            throw new ActionFailed(
                sprintf('Not possible to parse ticket ID from branch. See %s', self::DOC_URL)
            );
        }

        return $matches[1];
    }

    /**
     * @throws ActionFailed
     */
    private function extractProjectId(string $branch): string
    {
        $regex = sprintf(
            self::BRANCH_MATCH_FORMAT,
            self::TICKET_REGEX,
            '(' . self::PROJECT_ID_REGEX . ')'
        );

        preg_match($regex, $branch, $matches);
        if (isset($matches[1]) === false) {
            throw new ActionFailed(
                sprintf('Not possible to parse project ID from branch. See %s', self::DOC_URL)
            );
        }

        return $matches[1];
    }
}