<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class ArtifactFloatFieldXMLExporter extends ArtifactAlphaNumFieldXMLExporter {
    const TV3_VALUE_INDEX  = 'valueFloat';
    const TV3_TYPE         = 'TF_3';
    const TV5_TYPE         = 'float';

    public function appendNode(DOMElement $changeset_node, $tracker_id, $artifact_id, array $row) {
        $this->appendStringNode($changeset_node, self::TV5_TYPE, $row);
    }

    public function getFieldValueIndex() {
        return self::TV3_VALUE_INDEX;
    }

}
