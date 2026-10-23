<?php

namespace App\Http\Controllers;

use App\Mock\Repositories\DirectoryRepository;
use App\Mock\Repositories\PortalRepository;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LearningController extends Controller
{
    public const CATEGORIES = ['Call scripts', 'Playbooks', 'Process guides', 'Policy changes', 'Product training'];

    public function __construct(private PortalRepository $portal, private DirectoryRepository $dir) {}

    public function index()
    {
        return view('pages.learning.index', [
            'docs' => $this->portal->learning(), 'categories' => self::CATEGORIES, 'dir' => $this->dir,
            'roles' => collect(config('portal.roles'))->pluck('label')->all(), 'teams' => $this->dir->teams()->pluck('name')->all(), 'projects' => $this->dir->projects()->pluck('name')->all(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:140', 'category' => ['required', Rule::in(self::CATEGORIES)], 'description' => 'required|string|min:10|max:600',
            'audience_type' => ['required', Rule::in(['all', 'roles', 'teams', 'projects'])], 'audience_labels' => 'nullable|array', 'audience_labels.*' => 'string',
            'file_name' => 'nullable|string|max:200', 'file_type' => 'nullable|string|max:10', 'notify_in_app' => 'nullable|boolean', 'notify_email' => 'nullable|boolean',
        ], ['description.min' => 'Add a short description (min. 10 characters).']);
        if ($data['audience_type'] !== 'all' && empty($data['audience_labels'])) {
            return back()->withInput()->withErrors(['audience_labels' => 'Choose at least one '.rtrim($data['audience_type'], 's').' to target, or publish to everyone.']);
        }
        $data['audience_labels'] = $data['audience_type'] === 'all' ? ['Everyone'] : $data['audience_labels'];
        $data['size'] = ($data['file_name'] ?? '') ? 'demo file: '.$data['file_name'] : '— (demo, no upload)';
        $data['notify_in_app'] = $request->boolean('notify_in_app');
        $data['notify_email'] = $request->boolean('notify_email');
        $doc = $this->portal->publishLearning($data);
        $this->portal->log('Published learning document', $doc->id.' '.$doc->title, null, 'learning');

        return back()->with('toast', ['message' => '“'.$doc->title.'” published to '.implode(', ', $data['audience_labels']).'. '.($data['notify_in_app'] ? 'In-app notification queued. ' : '').($data['notify_email'] ? 'Email notification simulated.' : ''), 'demo' => $data['notify_email']]);
    }
}
