<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */
require_once AGILEDASHBOARD_BASE_DIR .'/AgileDashboard/Pane.class.php';
require_once 'common/mustache/MustacheRenderer.class.php';
require_once 'PaneContentPresenter.class.php';
require_once 'Column.class.php';
require_once 'Swimline.class.php';
require_once 'Mapping.class.php';

class Cardwall_Pane implements AgileDashboard_Pane {

    /**
     * @var Planning_Milestone
     */
    private $milestone;

    /**
     * @var array Accumulated array of Tracker_FormElement_Field_Selectbox
     */
    private $accumulated_status_fields = array();

    public function __construct(Planning_Milestone $milestone) {
        $this->milestone = $milestone;
        $this->milestone->getPlannedArtifacts()->accept($this);
    }

    public function visit(TreeNode $node) {
        $this->injectColumnFieldId($node);
        foreach ($node->getChildren() as $child) {
            $child->accept($this);
        }
    }

    private function injectColumnFieldId(TreeNode $node) {
        $data    = $node->getData();
        $tracker = $data['artifact']->getTracker();
        $field   = Tracker_Semantic_StatusFactory::instance()->getByTracker($tracker)->getField();
        $data['column_field_id'] = 0;
        if ($field) {
            $field_id = $field->getId();
            $data['column_field_id'] = $field_id;
            $this->accumulated_status_fields[$field_id] = $field;
        }
        $node->setData($data);
    }

    /**
     * @see AgileDashboard_Pane::getIdentifier()
     */
    public function getIdentifier() {
        return 'cardwall';
    }

    /**
     * @see AgileDashboard_Pane::getTitle()
     */
    public function getTitle() {
        return 'Card Wall';
    }

    /**
     * @see AgileDashboard_Pane::getContent()
     */
    public function getContent() {
        $tracker = $this->milestone->getPlanning()->getBacklogTracker();
        $field   = Tracker_Semantic_StatusFactory::instance()->getByTracker($tracker)->getField();
        if (!$field) {
            return 'Y u no configure the status semantic of ur tracker?';
        }

        $columns   = $this->getColumns($field);
        $swimlines = $this->getSwimlines($columns, $this->milestone->getPlannedArtifacts()->getChildren());
        $mappings  = $this->getMapping($field, $columns);

        $renderer  = new MustacheRenderer(dirname(__FILE__).'/../templates');
        $presenter = new Cardwall_PaneContentPresenter($swimlines, $columns, $mappings);
        ob_start();
        $renderer->render('pane-content', $presenter);
        return ob_get_clean();
    }
    
    /**
     * @return array of Cardwall_Mapping
     */
    private function getMapping(Tracker_FormElement_Field_Selectbox $field, array $columns) {
        $mappings = array();
        foreach ($this->accumulated_status_fields as $status_field) {
            foreach ($this->getFieldValues($status_field) as $value) {
                foreach ($columns as $column) {
                    if ($column->label == $value->getLabel()) {
                        $mappings[] = new Cardwall_Mapping($column->id, $status_field->getId(), $value->getId());
                    }
                }
            }
        }
        return $mappings;
    }

    private function getColumns(Tracker_FormElement_Field_Selectbox $field) {
        $values     = $this->getFieldValues($field);
        $decorators = $field->getBind()->getDecorators();
        $columns    = array();
        foreach ($values as $value) {
            $id = (int)$value->getId();
            $bgcolor = 'white';
            $fgcolor = 'black';
            if (isset($decorators[$id])) {
                $r = $decorators[$id]->r;
                $g = $decorators[$id]->g;
                $b = $decorators[$id]->b;
                if ($r !== null && $g !== null && $b !== null ) {
                    //choose a text color to have right contrast (black on dark colors is quite useless)
                    $bgcolor = 'rgb('. (int)$r .', '. (int)$g .', '. (int)$b .');';
                    $fgcolor = (0.3 * $r + 0.59 * $g + 0.11 * $b) < 128 ? 'white' : 'black';
                }
            }
            $columns[] = new Cardwall_Column($id, $value->getLabel(), $bgcolor, $fgcolor);
        }
        return $columns;
    }

    private function getFieldValues(Tracker_FormElement_Field_Selectbox $field) {
        $values = $field->getAllValues();
        foreach ($values as $key => $value) {
            if ($value->isHidden()) {
                unset($values[$key]);
            }
        }
        if ($values) {
            if (! $field->isRequired()) {
                $none = new Tracker_FormElement_Field_List_Bind_StaticValue(100, $GLOBALS['Language']->getText('global','none'), '', 0, false);
                $values = array_merge(array($none), $values);
            }
        }
        return $values;
    }

    private function getSwimlines(array $columns, array $nodes) {
        $swimlines = array();
        foreach ($nodes as $child) {
            $data  = $child->getData();
            $title = $data['artifact']->fetchTitle();
            $cells = $this->getCells($columns, $child->getChildren());
            $swimlines[] = new Cardwall_Swimline($title, $cells);
        }
        return $swimlines;
    }

    private function getCells(array $columns, array $nodes) {
        $cells = array();
        foreach ($columns as $column) {
            $cells[] = $this->getCell($column, $nodes);
        }
        return $cells;
    }

    private function getCell(Cardwall_Column $column, array $nodes) {
        $artifacts = array();
        foreach ($nodes as $node) {
            $this->addNodeToCell($node, $column, $artifacts);
        }
        return array('artifacts' => $artifacts);;
    }

    private function addNodeToCell(TreeNode $node, Cardwall_Column $column, array &$artifacts) {
        $data            = $node->getData();
        $artifact        = $data['artifact'];
        $artifact_status = $artifact->getStatus();
        if ($this->isArtifactInCell($artifact, $column)) {
            $artifacts[] = $node;
        }
    }

    private function isArtifactInCell(Tracker_Artifact $artifact, Cardwall_Column $column) {
        $artifact_status = $artifact->getStatus();
        return $artifact_status === $column->label || $artifact_status === null && $column->id == 100;
    }
}
?>
