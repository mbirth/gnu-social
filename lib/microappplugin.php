<?php
/**
 * StatusNet - the distributed open-source microblogging tool
 * Copyright (C) 2011, StatusNet, Inc.
 *
 * Superclass for microapp plugin
 * 
 * PHP version 5
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  Microapp
 * @package   StatusNet
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPL 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET')) {
    // This check helps protect against security problems;
    // your code file can't be executed directly from the web.
    exit(1);
}

/**
 * Superclass for microapp plugins
 *
 * This class lets you define micro-applications with different kinds of activities.
 *
 * The applications work more-or-less like other 
 * 
 * @category  Microapp
 * @package   StatusNet
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPL 3.0
 * @link      http://status.net/
 */

abstract class MicroAppPlugin extends Plugin
{
    /**
     * Returns a localized string which represents this micro-app,
     * to be shown to users selecting what type of post to make.
     * This is paired with the key string in $this->tag().
     *
     * All micro-app classes must override this method.
     *
     * @return string
     */
    abstract function appTitle();

    /**
     * Returns a key string which represents this micro-app in HTML
     * ids etc, as when offering selection of what type of post to make.
     * This is paired with the user-visible localizable $this->appTitle().
     *
     * All micro-app classes must override this method.
     */
    abstract function tag();

    /**
     * Return a list of ActivityStreams object type URIs
     * which this micro-app handles. Default implementations
     * of the base class will use this list to check if a
     * given ActivityStreams object belongs to us, via
     * $this->isMyNotice() or $this->isMyActivity.
     *
     * All micro-app classes must override this method.
     *
     * @fixme can we confirm that these types are the same
     * for Atom and JSON streams? Any limitations or issues?
     *
     * @return array of strings
     */
    abstract function types();

    /**
     * Given a parsed ActivityStreams activity, your plugin
     * gets to figure out how to actually save it into a notice
     * and any additional data structures you require.
     *
     * This will handle things received via AtomPub, OStatus
     * (PuSH and Salmon transports), or ActivityStreams-based
     * backup/restore of account data.
     *
     * You should be able to accept as input the output from your
     * $this->activityObjectFromNotice(). Where applicable, try to
     * use existing ActivityStreams structures and object types,
     * and be liberal in accepting input from what might be other
     * compatible apps.
     *
     * All micro-app classes must override this method.
     *
     * @fixme are there any standard options?
     *
     * @param Activity $activity
     * @param Profile $actor
     * @param array $options=array()
     *
     * @return Notice the resulting notice
     */
    abstract function saveNoticeFromActivity($activity, $actor, $options=array());

    /**
     * Given an existing Notice object, your plugin gets to
     * figure out how to arrange it into an ActivityStreams
     * object.
     *
     * This will be how your specialized notice gets output in
     * Atom feeds and JSON-based ActivityStreams output, including
     * account backup/restore and OStatus (PuSH and Salmon transports).
     *
     * You should be able to round-trip data from this format back
     * through $this->saveNoticeFromActivity(). Where applicable, try
     * to use existing ActivityStreams structures and object types,
     * and consider interop with other compatible apps.
     *
     * All micro-app classes must override this method.
     *
     * @fixme this outputs an ActivityObject, not an Activity. Any compat issues?
     *
     * @param Notice $notice
     *
     * @return ActivityObject
     */
    abstract function activityObjectFromNotice($notice);

    /**
     * Custom HTML output for your special notice; called when a
     * matching notice turns up in a NoticeListItem.
     *
     * All micro-app classes must override this method.
     *
     * @param Notice $notice
     * @param HTMLOutputter $out
     *
     * @fixme WARNING WARNING WARNING base plugin stuff below tries to close
     * a div that this function opens in the BookmarkPlugin child class.
     * This is probably wrong.
     */
    abstract function showNotice($notice, $out);

    /**
     * When building the primary notice form, we'll fetch also some
     * alternate forms for specialized types -- that's you!
     *
     * Return a custom Widget or Form object for the given output
     * object, and it'll be included in the HTML output. Beware that
     * your form may be initially hidden.
     *
     * All micro-app classes must override this method.
     *
     * @param HTMLOutputter $out
     * @return Widget
     */
    abstract function entryForm($out);

    /**
     * When a notice is deleted, you'll be called here for a chance
     * to clean up any related resources.
     *
     * All micro-app classes must override this method.
     *
     * @param Notice $notice
     */
    abstract function deleteRelated($notice);

    /**
     * Check if a given notice object should be handled by this micro-app
     * plugin.
     *
     * The default implementation checks against the activity type list
     * returned by $this->types(). You can override this method to expand
     * your checks.
     *
     * @param Notice $notice
     * @return boolean
     */
    function isMyNotice($notice) {
        $types = $this->types();
        return in_array($notice->object_type, $types);
    }

    /**
     * Check if a given ActivityStreams activity should be handled by this
     * micro-app plugin.
     *
     * The default implementation checks against the activity type list
     * returned by $this->types(), and requires that exactly one matching
     * object be present. You can override this method to expand
     * your checks or to compare the activity's verb, etc.
     *
     * @param Activity $activity
     * @return boolean
     */
    function isMyActivity($activity) {
        $types = $this->types();
        return (count($activity->objects) == 1 &&
                in_array($activity->objects[0]->type, $types));
    }

    /**
     * When a notice is deleted, delete the related objects
     * by calling the overridable $this->deleteRelated().
     *
     * @param Notice $notice Notice being deleted
     * 
     * @return boolean hook value
     */

    function onNoticeDeleteRelated($notice)
    {
        if ($this->isMyNotice($notice)) {
            $this->deleteRelated($notice);
        }

        return true;
    }

    /**
     * Output the HTML for this kind of object in a list
     *
     * @param NoticeListItem $nli The list item being shown.
     *
     * @return boolean hook value
     *
     * @fixme WARNING WARNING WARNING this closes a 'div' that is implicitly opened in BookmarkPlugin's showNotice implementation
     */

    function onStartShowNoticeItem($nli)
    {
        if (!$this->isMyNotice($nli->notice)) {
            return true;
        }

        $out = $nli->out;
        $notice = $nli->notice;

        $this->showNotice($notice, $out);

        $nli->showNoticeLink();
        $nli->showNoticeSource();
        $nli->showNoticeLocation();
        $nli->showContext();
        $nli->showRepeat();
        
        $out->elementEnd('div');
        
        $nli->showNoticeOptions();

        return false;
    }

    /**
     * Render a notice as one of our objects
     *
     * @param Notice         $notice  Notice to render
     * @param ActivityObject &$object Empty object to fill
     *
     * @return boolean hook value
     */
     
    function onStartActivityObjectFromNotice($notice, &$object)
    {
        if ($this->isMyNotice($notice)) {
            $object = $this->activityObjectFromNotice($notice);
            return false;
        }

        return true;
    }

    /**
     * Handle a posted object from PuSH
     *
     * @param Activity        $activity activity to handle
     * @param Ostatus_profile $oprofile Profile for the feed
     *
     * @return boolean hook value
     */

    function onStartHandleFeedEntryWithProfile($activity, $oprofile)
    {
        if ($this->isMyActivity($activity)) {

            $actor = $oprofile->checkAuthorship($activity);

            if (empty($actor)) {
                throw new ClientException(_('Can\'t get author for activity.'));
            }

            $object = $activity->objects[0];

            $options = array('uri' => $object->id,
                             'url' => $object->link,
                             'is_local' => Notice::REMOTE_OMB,
                             'source' => 'ostatus');
            
            $this->saveNoticeFromActivity($activity, $actor);

            return false;
        }

        return true;
    }

    /**
     * Handle a posted object from Salmon
     *
     * @param Activity $activity activity to handle
     * @param mixed    $target   user or group targeted
     *
     * @return boolean hook value
     */

    function onStartHandleSalmonTarget($activity, $target)
    {
        if ($this->isMyActivity($activity)) {

            $this->log(LOG_INFO, "Checking {$activity->id} as a valid Salmon slap.");

            if ($target instanceof User_group) {
                $uri = $target->getUri();
                if (!in_array($uri, $activity->context->attention)) {
                    throw new ClientException(_("Bookmark not posted ".
                                                "to this group."));
                }
            } else if ($target instanceof User) {
                $uri      = $target->uri;
                $original = null;
                if (!empty($activity->context->replyToID)) {
                    $original = Notice::staticGet('uri', 
                                                  $activity->context->replyToID); 
                }
                if (!in_array($uri, $activity->context->attention) &&
                    (empty($original) ||
                     $original->profile_id != $target->id)) {
                    throw new ClientException(_("Object not posted ".
                                                "to this user."));
                }
            } else {
                throw new ServerException(_("Don't know how to handle ".
                                            "this kind of target."));
            }

            $actor = Ostatus_profile::ensureActivityObjectProfile($activity->actor);

            $object = $activity->objects[0];

            $options = array('uri' => $object->id,
                             'url' => $object->link,
                             'is_local' => Notice::REMOTE_OMB,
                             'source' => 'ostatus');

            $this->saveNoticeFromActivity($activity, $actor, $options);

            return false;
        }

        return true;
    }

    /**
     * Handle object posted via AtomPub
     *
     * @param Activity &$activity Activity that was posted
     * @param User     $user      User that posted it
     * @param Notice   &$notice   Resulting notice
     *
     * @return boolean hook value
     */

    function onStartAtomPubNewActivity(&$activity, $user, &$notice)
    {
        if ($this->isMyActivity($activity)) {

            $options = array('source' => 'atompub');

            $this->saveNoticeFromActivity($activity,
                                          $user->getProfile(),
                                          $options);

            return false;
        }

        return true;
    }

    /**
     * Handle object imported from a backup file
     *
     * @param User           $user     User to import for
     * @param ActivityObject $author   Original author per import file
     * @param Activity       $activity Activity to import
     * @param boolean        $trusted  Is this a trusted user?
     * @param boolean        &$done    Is this done (success or unrecoverable error)
     *
     * @return boolean hook value
     */

    function onStartImportActivity($user, $author, $activity, $trusted, &$done)
    {
        if ($this->isMyActivity($activity)) {

            $obj = $activity->objects[0];

            $options = array('uri' => $object->id,
                             'url' => $object->link,
                             'source' => 'restore');

            $saved = $this->saveNoticeFromActivity($activity,
                                                   $user->getProfile(),
                                                   $options);

            if (!empty($saved)) {
                $done = true;
            }

            return false;
        }

        return true;
    }

    function onStartShowEntryForms(&$tabs)
    {
        $tabs[$this->tag()] = $this->appTitle();
        return true;
    }

    function onStartMakeEntryForm($tag, $out, &$form)
    {
        $this->log(LOG_INFO, "onStartMakeEntryForm() called for tag '$tag'");

        if ($tag == $this->tag()) {
            $form = $this->entryForm($out);
            return false;
        }

        return true;
    }
}
