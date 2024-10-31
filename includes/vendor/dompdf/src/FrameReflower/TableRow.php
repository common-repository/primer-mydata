<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace PrimerDompdf\FrameReflower;

use PrimerDompdf\FrameDecorator\Block as BlockFrameDecorator;
use PrimerDompdf\FrameDecorator\Table as TableFrameDecorator;
use PrimerDompdf\FrameDecorator\TableRow as TableRowFrameDecorator;
use PrimerDompdf\Exception;

/**
 * Reflows table rows
 *
 * @package dompdf
 */
class TableRow extends AbstractFrameReflower
{
    /**
     * TableRow constructor.
     * @param TableRowFrameDecorator $frame
     */
    function __construct(TableRowFrameDecorator $frame)
    {
        parent::__construct($frame);
    }

    /**
     * @param BlockFrameDecorator|null $block
     */
    function reflow(BlockFrameDecorator $block = null)
    {
        /** @var TableRowFrameDecorator */
        $frame = $this->_frame;

        // Check if a page break is forced
        $page = $frame->get_root();
        $page->check_forced_page_break($frame);

        // Bail if the page is full
        if ($page->is_full()) {
            return;
        }

        // Counters and generated content
        $this->_set_content();

        $this->_frame->position();
        $style = $this->_frame->get_style();
        $cb = $this->_frame->get_containing_block();

        foreach ($this->_frame->get_children() as $child) {
            $child->set_containing_block($cb);
            $child->reflow();

            if ($page->is_full()) {
                break;
            }
        }

        if ($page->is_full()) {
            return;
        }

        $table = TableFrameDecorator::find_parent_table($this->_frame);
        $cellmap = $table->get_cellmap();
        $style->width = $cellmap->get_frame_width($this->_frame);
        $style->height = $cellmap->get_frame_height($this->_frame);

        $this->_frame->set_position($cellmap->get_frame_position($this->_frame));
    }

    /**
     * @throws Exception
     */
    public function get_min_max_width(): array
    {
        throw new Exception("Min/max width is undefined for table rows");
    }
}
