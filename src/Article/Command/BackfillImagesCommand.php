<?php

declare(strict_types=1);

namespace App\Article\Command;

use App\Article\Repository\ArticleRepositoryInterface;
use App\Article\Service\ArticleImageExtractor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:articles:backfill-images',
    description: 'Extract and persist image URLs for articles missing thumbnails',
)]
final class BackfillImagesCommand extends Command
{
    public function __construct(
        private readonly ArticleRepositoryInterface $articleRepository,
        private readonly ArticleImageExtractor $imageExtractor,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Batch size per iteration', '100')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show count without updating');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        /** @var string $limitStr */
        $limitStr = $input->getOption('limit');
        $limit = max(1, (int) $limitStr);
        $dryRun = $input->getOption('dry-run') === true;

        $updated = 0;
        $offset = 0;

        while (true) {
            $articles = $this->articleRepository->findWithoutImageUrl($limit, $offset);
            if ($articles === []) {
                break;
            }

            foreach ($articles as $article) {
                $imageUrl = $this->imageExtractor->extract(null, $article->getContentRaw(), $article->getUrl());
                if ($imageUrl === null) {
                    continue;
                }

                if ($dryRun) {
                    ++$updated;
                    continue;
                }

                $article->setImageUrl($imageUrl);
                ++$updated;
            }

            if ($dryRun) {
                $offset += $limit;
                if (count($articles) < $limit) {
                    break;
                }
                continue;
            }

            $this->articleRepository->flush();
            $offset += $limit;

            if (count($articles) < $limit) {
                break;
            }
        }

        if ($updated === 0) {
            $io->success('No article images to backfill.');

            return Command::SUCCESS;
        }

        if ($dryRun) {
            $io->note(sprintf('Dry run — %d articles would receive an image URL.', $updated));

            return Command::SUCCESS;
        }

        $io->success(sprintf('Updated %d articles with image URLs.', $updated));

        return Command::SUCCESS;
    }
}
