<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace PrimerDompdf\FrameDecorator;

use PrimerDompdf\Dompdf;
use PrimerDompdf\Frame;
use PrimerDompdf\FrameDecorator\Table as TableFrameDecorator;

/**
 * Decorates Frames for table row layout
 *
 * @package dompdf
 */
class TableRow extends AbstractFrameDecorator
{
    /**
     * TableRow constructor.
     * @param Frame $frame
     * @param Dompdf $dompdf
     */
    function __construct(Frame $frame, Dompdf $dompdf)
    {
        parent::__construct($frame, $dompdf);
    }

    //........................................................................

    /**
     * Remove all non table-cell frames from this row and move them after
     * the table.
     *
     * @deprecated
     */
    function normalise()
    {
        // Find our table parent
        $p = TableFrameDecorator::find_parent_table($this);

        $erroneous_frames = [];
        foreach ($this->get_children() as $child) {
            $display = $child->get_style()->display;

            if ($display !== "table-cell") {
                $erroneous_frames[] = $child;
            }
        }

        //  dump the extra nodes after the table.
        foreach ($erroneous_frames as $frame) {
            $p->move_after($frame);
        }
    }
}
