<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

namespace Tuleap\Project\Admin\ProjectUGroup;

use CSRFSynchronizerToken;
use Project;
use ProjectUGroup;
use Tuleap\User\UserGroup\NameTranslator;
use UGroupManager;

class UGroupListPresenterBuilder
{
    /**
     * @var UGroupManager
     */
    private $ugroup_manager;

    public function __construct(UGroupManager $ugroup_manager)
    {
        $this->ugroup_manager = $ugroup_manager;
    }

    public function build(Project $project, CSRFSynchronizerToken $csrf)
    {
        $static_ugroups = $this->getStaticUGroups($project);
        $templates      = $this->getUGroupsThatCanBeUsedAsTemplate($project, $static_ugroups);

        return new UGroupListPresenter(
            $project,
            $this->getDynamicUGroups($project),
            $this->getStaticUGroupsPresenters($project, $static_ugroups),
            $templates,
            $csrf
        );
    }

    /**
     * @param \ProjectUGroup[] $static_ugroups
     * @return array
     */
    private function getUGroupsThatCanBeUsedAsTemplate(Project $project, array $static_ugroups)
    {
        $ugroups   = [];
        $ugroups[] = array(
            'id'       => 'cx_empty',
            'name'     => _('Empty group'),
            'selected' => 'selected="selected"'
        );

        if ($project->isLegacyDefaultTemplate()) {
            return $ugroups;
        }

        $ugroups[] = array(
            'id'       => 'cx_members',
            'name'     => NameTranslator::getUserGroupDisplayName(NameTranslator::PROJECT_MEMBERS),
            'selected' => ''
        );

        $ugroups[] = array(
            'id'       => 'cx_admins',
            'name'     => NameTranslator::getUserGroupDisplayName(NameTranslator::PROJECT_ADMINS),
            'selected' => ''
        );

        foreach ($static_ugroups as $ugroup) {
            $ugroups[] = array(
                'id'       => $ugroup->getId(),
                'name'     => NameTranslator::getUserGroupDisplayName($ugroup->getName()),
                'selected' => ''
            );
        }

        return $ugroups;
    }

    private function injectDynamicUGroup(Project $project, $ugroup_id, &$ugroups)
    {
        $ugroup         = $this->ugroup_manager->getUGroup($project, $ugroup_id);
        $can_be_deleted = false;
        $ugroups[]      = new UGroupPresenter($project, $ugroup, $can_be_deleted);
    }

    /**
     * @param Project $project
     * @return UGroupPresenter[]
     */
    private function getDynamicUGroups(Project $project)
    {
        if ($project->isLegacyDefaultTemplate()) {
            return [];
        }

        $ugroups = array();
        $this->injectDynamicUGroup($project, ProjectUGroup::PROJECT_ADMIN, $ugroups);
        if ($project->usesWiki()) {
            $this->injectDynamicUGroup($project, ProjectUGroup::WIKI_ADMIN, $ugroups);
        }

        if ($project->usesForum()) {
            $this->injectDynamicUGroup($project, ProjectUGroup::FORUM_ADMIN, $ugroups);
        }

        if ($project->usesNews()) {
            $this->injectDynamicUGroup($project, ProjectUGroup::NEWS_WRITER, $ugroups);
            $this->injectDynamicUGroup($project, ProjectUGroup::NEWS_ADMIN, $ugroups);
        }

        return $ugroups;
    }

    /**
     * @param Project $project
     * @param ProjectUGroup[] $static_ugroups
     * @return UGroupPresenter[]
     */
    private function getStaticUGroupsPresenters(Project $project, array $static_ugroups)
    {
        $presenters     = [];
        $can_be_deleted = true;
        foreach ($static_ugroups as $ugroup) {
            $presenters[] = new UGroupPresenter($project, $ugroup, $can_be_deleted);
        }

        return $presenters;
    }

    /**
     * @param Project $project
     * @return ProjectUGroup[]
     */
    private function getStaticUGroups(Project $project)
    {
        $static_ugroups = $this->ugroup_manager->getStaticUGroups($project);

        // Default template (project id 100) does not know the difference between
        // a dynamic ugroup (that typically belongs to project id 100 for every project)
        // and a static ugroup (that belongs to the current project id 100)
        // Therefore we need to manually remove those dynamic ugroups.
        if ($project->isLegacyDefaultTemplate()) {
            $static_ugroups = $this->removeDynamicUGroups($static_ugroups);
        }

        return $static_ugroups;
    }

    private function removeDynamicUGroups(array &$static_ugroups)
    {
        return array_filter($static_ugroups, function (ProjectUGroup $ugroup) {
            return $ugroup->getId() > 100;
        });
    }
}
