<?php
/**
 * Copyright Enalean (c) 2017. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Project\Admin\ProjectMembers;

use CSRFSynchronizerToken;
use Project;

class ProjectMembersPresenter
{

    /**
     * @var array
     */
    public $project_members_list;
    public $csrf_token;
    public $project_id;

    public function __construct(
        array $project_members_list,
        CSRFSynchronizerToken $csrf_token,
        Project $project
    ) {
        $this->project_members_list = $project_members_list;
        $this->csrf_token           = $csrf_token;
        $this->project_id           = $project->getID();
    }
}