<?php

declare(strict_types=1);

namespace Alcor\Tests\Unit\Payroll\Infrastructure;

use Alcor\Payroll\Domain\AdjustmentComment;
use Alcor\Payroll\Domain\EarningLine;
use Alcor\Payroll\Domain\EarningLineId;
use Alcor\Payroll\Domain\Exception\EarningLineNotFound;
use Alcor\Payroll\Domain\Money;
use Alcor\Payroll\Domain\SpecialistId;
use Alcor\Payroll\Infrastructure\EventSourcedEarningLineRepository;
use Alcor\Shared\Application\ConcurrencyConflict;
use Alcor\Shared\Infrastructure\InMemoryEventStore;
use Alcor\Tests\Support\RecordingEventPublisher;
use PHPUnit\Framework\TestCase;

final class EventSourcedEarningLineRepositoryTest extends TestCase
{
    private InMemoryEventStore $eventStore;

    private RecordingEventPublisher $publisher;

    private EventSourcedEarningLineRepository $repository;

    protected function setUp(): void
    {
        $this->eventStore = new InMemoryEventStore();
        $this->publisher = new RecordingEventPublisher();
        $this->repository = new EventSourcedEarningLineRepository($this->eventStore, $this->publisher);
    }

    public function test_a_saved_line_is_rebuilt_from_its_events(): void
    {
        $line = $this->newLine();
        $this->adjust($line, '-45.55');
        $this->repository->save($line);

        $loaded = $this->repository->get(new EarningLineId('line-1'));

        self::assertSame(2, $loaded->version());
        self::assertSame([], $loaded->releaseEvents());
        self::assertSame($this->eventStore->load('line-1'), $this->publisher->published);
        self::assertTrue($this->repository->exists(new EarningLineId('line-1')));
    }

    public function test_saving_publishes_only_new_events(): void
    {
        $this->repository->save($this->newLine());
        $line = $this->repository->get(new EarningLineId('line-1'));
        $this->adjust($line, '-45.55');

        $this->repository->save($line);

        self::assertCount(2, $this->publisher->published);
        self::assertCount(2, $this->eventStore->load('line-1'));
    }

    public function test_saving_without_changes_is_a_no_op(): void
    {
        $this->repository->save($this->newLine());
        $this->repository->save($this->repository->get(new EarningLineId('line-1')));

        self::assertCount(1, $this->publisher->published);
    }

    public function test_concurrent_modifications_are_detected(): void
    {
        $this->repository->save($this->newLine());
        $first = $this->repository->get(new EarningLineId('line-1'));
        $second = $this->repository->get(new EarningLineId('line-1'));
        $this->adjust($first, '-45.55');
        $this->adjust($second, '100.10');
        $this->repository->save($first);

        $this->expectException(ConcurrencyConflict::class);

        $this->repository->save($second);
    }

    public function test_missing_lines_are_reported(): void
    {
        self::assertFalse($this->repository->exists(new EarningLineId('missing')));

        $this->expectException(EarningLineNotFound::class);

        $this->repository->get(new EarningLineId('missing'));
    }

    private function newLine(): EarningLine
    {
        return EarningLine::calculate(new EarningLineId('line-1'), Money::fromDecimal('1050.00'), new \DateTimeImmutable());
    }

    private function adjust(EarningLine $line, string $amount): void
    {
        $line->addAdjustment(
            Money::fromDecimal($amount),
            new AdjustmentComment('Correction'),
            new SpecialistId('specialist-1'),
            new \DateTimeImmutable(),
        );
    }
}
