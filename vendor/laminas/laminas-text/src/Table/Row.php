<?php

namespace Laminas\Text\Table;

use Laminas\Text\Table\Column;
use Laminas\Text\Table\Decorator\DecoratorInterface as Decorator;

use function array_slice;
use function array_sum;
use function count;
use function explode;
use function max;
use function str_repeat;

/**
 * Row class for Laminas\Text\Table
 */
class Row
{
    /**
     * List of all columns
     *
     * @var array
     */
    protected $columns = [];

    /**
     * Temporary stored column widths
     *
     * @var array|null
     */
    protected $columnWidths;

    /**
     * Create a new column and append it to the row
     *
     * @param string                                                                      $content
     * @param array{align?: string|null, colSpan?: int|null, encoding?: string|null}|null $options
     * @return Row
     */
    public function createColumn($content, ?array $options = null)
    {
        $align    = null;
        $colSpan  = null;
        $encoding = null;

        if ($options !== null) {
            $align    = $options['align'] ?? null;
            $colSpan  = $options['colSpan'] ?? null;
            $encoding = $options['encoding'] ?? null;
        }

        $column = new Column($content, $align, $colSpan, $encoding);

        $this->appendColumn($column);

        return $this;
    }

    /**
     * Append a column to the row
     *
     * @param  Column $column The column to append to the row
     * @return Row
     */
    public function appendColumn(Column $column)
    {
        $this->columns[] = $column;

        return $this;
    }

    /**
     * Get a column by it's index
     *
     * Returns null, when the index is out of range
     *
     * @param  int $index
     * @return Column|null
     */
    public function getColumn($index)
    {
        if (! isset($this->columns[$index])) {
            return;
        }

        return $this->columns[$index];
    }

    /**
     * Get all columns of the row
     *
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Get the widths of all columns, which were rendered last
     *
     * @throws Exception\UnexpectedValueException When no columns were rendered yet.
     * @return int
     */
    public function getColumnWidths()
    {
        if ($this->columnWidths === null) {
            throw new Exception\UnexpectedValueException(
                'render() must be called before columnWidths can be populated'
            );
        }

        return $this->columnWidths;
    }

    /**
     * Render the row
     *
     * @param  array                               $columnWidths Width of all columns
     * @param  Decorator $decorator    Decorator for the row borders
     * @param  int                             $padding      Padding for the columns
     * @throws Exception\OverflowException When there are too many columns.
     * @return string
     */
    public function render(array $columnWidths, Decorator $decorator, $padding = 0)
    {
        // Prepare an array to store all column widths
        $this->columnWidths = [];

        // If there is no single column, create a column which spans over the
        // entire row
        if (! $this->columns) {
            $this->appendColumn(new Column(null, null, count($columnWidths)));
        }

        // First we have to render all columns, to get the maximum height
        $renderedColumns = [];
        $maxHeight       = 0;
        $colNum          = 0;
        foreach ($this->columns as $column) {
            // Get the colspan of the column
            $colSpan = $column->getColSpan();

            // Verify if there are enough column widths defined
            if (($colNum + $colSpan) > count($columnWidths)) {
                throw new Exception\OverflowException('Too many columns');
            }

            // Calculate the column width
            $columnWidth = $colSpan - 1 + array_sum(array_slice($columnWidths, $colNum, $colSpan));

            // Render the column and split it's lines into an array
            $result = explode("\n", $column->render($columnWidth, $padding));

            // Store the width of the rendered column
            $this->columnWidths[] = $columnWidth;

            // Store the rendered column and calculate the new max height
            $renderedColumns[] = $result;
            $maxHeight         = max($maxHeight, count($result));

            // Set up the internal column number
            $colNum += $colSpan;
        }

        // If the row doesnt contain enough columns to fill the entire row, fill
        // it with an empty column
        if ($colNum < count($columnWidths)) {
            $remainingWidth    = (count($columnWidths) - $colNum - 1) +
                               array_sum(array_slice($columnWidths, $colNum));
            $renderedColumns[] = [str_repeat(' ', $remainingWidth)];

            $this->columnWidths[] = $remainingWidth;
        }

        // Add each single column line to the result
        $result = '';
        for ($line = 0; $line < $maxHeight; $line++) {
            $result .= $decorator->getVertical();

            foreach ($renderedColumns as $index => $renderedColumn) {
                if (isset($renderedColumn[$line]) === true) {
                    $result .= $renderedColumn[$line];
                } else {
                    $result .= str_repeat(' ', $this->columnWidths[$index]);
                }

                $result .= $decorator->getVertical();
            }

            $result .= "\n";
        }

        return $result;
    }
}
