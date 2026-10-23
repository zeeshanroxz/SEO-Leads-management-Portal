<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mock\Repositories\DirectoryRepository;
use App\Mock\Repositories\FormRepository;
use App\Mock\Repositories\PortalRepository;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FormBuilderController extends Controller
{
    private const TYPES = ['text', 'short_answer', 'radio', 'dropdown', 'checkbox', 'number', 'percentage'];

    public function __construct(private FormRepository $forms, private DirectoryRepository $dir, private PortalRepository $portal) {}

    public function index()
    {
        return view('pages.admin.forms-index', ['forms' => $this->forms->forms(), 'fixed' => $this->forms->fixedFields(), 'dir' => $this->dir]);
    }

    public function show(string $form)
    {
        $f = $this->forms->form($form);
        abort_unless($f, 404);
        $draft = $this->forms->draftVersion($f->id);
        $published = $this->forms->publishedVersion($f->id);
        $working = $draft ?? $published;
        $publishedKeys = $f->versions ? collect($f->versions)->where('status', '!=', 'draft')->flatMap(fn ($v) => collect($v->sections)->flatMap(fn ($s) => collect($s->fields)->pluck('key')))->unique()->values()->all() : [];

        return view('pages.admin.forms-show', [
            'form' => $f, 'draft' => $draft, 'published' => $published, 'working' => $working->plain(), 'publishedKeys' => $publishedKeys,
            'project' => $this->dir->project($f->project_id), 'versionsJson' => collect($f->versions)->map(fn ($v) => $v->plain())->values()->all(),
        ]);
    }

    private function sections(Request $request): array
    {
        $sections = $request->input('sections');
        if (is_string($sections)) {
            $sections = json_decode($sections, true);
        }
        validator(['sections' => $sections], [
            'sections' => 'required|array|min:1', 'sections.*.title' => 'required|string|max:80',
            'sections.*.fields' => 'array', 'sections.*.fields.*.label' => 'required|string|max:160',
            'sections.*.fields.*.key' => 'required|string|max:60', 'sections.*.fields.*.type' => ['required', Rule::in(self::TYPES)],
            'sections.*.fields.*.options' => 'array',
        ])->validate();

        return array_values($sections);
    }

    public function draft(Request $request, string $form)
    {
        $this->forms->saveDraft($form, $this->sections($request));
        $this->portal->log('Form draft saved', $form, null, 'config');

        return response()->json(['ok' => true, 'message' => 'Draft saved. Leads keep using the published version until you publish.']);
    }

    public function publish(Request $request, string $form)
    {
        $data = $request->validate(['note' => 'required|string|min:5|max:200'], ['note.required' => 'Add a short change note for the version history.']);
        $this->forms->saveDraft($form, $this->sections($request));
        $v = $this->forms->publish($form, $data['note']);
        $this->portal->log('Published form version', $form.' v'.$v?->versions[0]->version ?? '', $data['note'], 'config');

        return redirect()->route('forms.show', $form)->with('toast', ['message' => 'New form version published. Past submissions keep the version they used.']);
    }

    public function discard(string $form)
    {
        $this->forms->discardDraft($form);

        return redirect()->route('forms.show', $form)->with('toast', ['message' => 'Draft discarded.', 'type' => 'info']);
    }

    public function fixed(Request $request)
    {
        $data = $request->validate(['id' => 'required|string', 'required' => 'required|boolean']);
        $this->forms->setFixedRequired($data['id'], (bool) $data['required']);
        $this->portal->log('Fixed field requirement changed', $data['id'], $data['required'] ? 'now required' : 'now optional', 'config');

        return back()->with('toast', ['message' => 'Fixed field updated.']);
    }
}
