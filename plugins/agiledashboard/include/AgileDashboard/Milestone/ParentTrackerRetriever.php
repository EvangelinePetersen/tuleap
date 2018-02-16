<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Milestone;

use PFUser;
use Planning_Milestone;
use PlanningFactory;
use Tracker;

class ParentTrackerRetriever
{

    /**
     * @var PlanningFactory
     */
    private $planning_factory;

    public function __construct(PlanningFactory $planning_factory)
    {
        $this->planning_factory = $planning_factory;
    }

    /**
     * @return Tracker[]
     */
    public function getCreatableParentTrackers(
        Planning_Milestone $milestone,
        PFUSer $user,
        array $descendant_backlog_trackers
    ) {
        $parent_trackers                = [];
        $descendant_backlog_tracker_ids = [];
        foreach ($descendant_backlog_trackers as $backlog_tracker) {
            $descendant_backlog_tracker_ids[] = $backlog_tracker->getId();
            if ($backlog_tracker->getParent()) {
                $parent_trackers[] = $backlog_tracker->getParent();
            }
        }

        $all_plannings = $this->planning_factory->getOrderedPlanningsWithBacklogTracker($user, $milestone->getGroupId());
        $sub_plannings = [];
        foreach ($all_plannings as $key => $planning) {
            if ($planning->getId() == $milestone->getPlanning()->getId()) {
                $sub_plannings = array_slice($all_plannings, $key+1);
                break;
            }
        }

        $sub_backlog_tracker_ids = [];
        foreach ($sub_plannings as $sub_planning) {
            $sub_backlog_tracker_ids = array_merge($sub_backlog_tracker_ids, $sub_planning->getBacklogTrackersIds());
        }

        $excluded_tracker_ids = array_merge($descendant_backlog_tracker_ids, $sub_backlog_tracker_ids);

        return array_filter($parent_trackers, function (Tracker $tracker) use ($excluded_tracker_ids) {
            return ! in_array($tracker->getId(), $excluded_tracker_ids);
        });
    }
}