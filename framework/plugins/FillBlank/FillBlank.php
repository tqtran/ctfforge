<?php
class FillBlank extends PluginBase {
    public function getType(): string {
        return 'fill_blank';
    }

    public function renderForm(array $data = []): string {
        $datasets = $data['datasets'] ?? [['data_value' => '', 'acceptable_answers' => '']];
        $html = '<div class="mb-3">';
        $html .= '<label class="form-label">Template <small class="text-muted">(use {data} as placeholder)</small></label>';
        $html .= '<input type="text" class="form-control" name="template" value="' . htmlspecialchars($data['template'] ?? 'What is {data}?') . '" required>';
        $html .= '</div>';
        $html .= '<div id="datasets-container">';
        foreach ($datasets as $i => $ds) {
            $html .= $this->renderDatasetRow($i, $ds);
        }
        $html .= '</div>';
        $html .= '<button type="button" class="btn btn-secondary btn-sm mt-2" onclick="addDatasetRow()"><i class="fas fa-plus"></i> Add Dataset Row</button>';
        return $html;
    }

    private function renderDatasetRow(int $index, array $ds): string {
        $dataValue = htmlspecialchars($ds['data_value'] ?? '');
        $answers = htmlspecialchars($ds['acceptable_answers'] ?? '');
        return '<div class="dataset-row border rounded p-3 mb-2" data-index="' . $index . '">'
            . '<div class="d-flex justify-content-between align-items-center mb-2">'
            . '<strong>Dataset Row #<span class="row-num">' . ($index + 1) . '</span></strong>'
            . ($index > 0 ? '<button type="button" class="btn btn-danger btn-sm" onclick="removeDatasetRow(this)"><i class="fas fa-trash"></i></button>' : '')
            . '</div>'
            . '<div class="mb-2"><label class="form-label">Data Value</label>'
            . '<input type="text" class="form-control" name="datasets[' . $index . '][data_value]" value="' . $dataValue . '" required></div>'
            . '<div class="mb-2"><label class="form-label">Acceptable Answers <small class="text-muted">(comma-separated)</small></label>'
            . '<input type="text" class="form-control" name="datasets[' . $index . '][acceptable_answers]" value="' . $answers . '" required></div>'
            . '</div>';
    }

    public function renderQuestion(array $dataset): string {
        $question = str_replace('{data}', htmlspecialchars($dataset['data_value']), htmlspecialchars($dataset['template'] ?? ''));
        return '<div class="mb-3"><p class="fw-bold">' . $question . '</p>'
            . '<input type="text" class="form-control" name="answer" placeholder="Your answer..." required>'
            . '</div>';
    }

    public function checkAnswer(array $dataset, string $answer): bool {
        $acceptable = array_map('trim', explode(',', $dataset['acceptable_answers']));
        $answer = trim($answer);
        foreach ($acceptable as $acc) {
            if (strcasecmp($acc, $answer) === 0) {
                return true;
            }
        }
        return false;
    }
}
