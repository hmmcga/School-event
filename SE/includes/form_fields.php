<?php
/**
 * renderField($name, $config, $value, $pdo)
 * Echoes a <div class="form-group"> for one field, based on config/modules.php
 */
function renderField(string $name, array $config, $value, PDO $pdo): void
{
    $label    = htmlspecialchars($config['label']);
    $required = !empty($config['required']) ? 'required' : '';
    $value    = $value ?? '';
    $minAttr  = '';

    if (in_array($name, ['start_date', 'end_date', 'booking_date'], true)) {
        $minAttr = ' min="' . htmlspecialchars(date('Y-m-d')) . '"';
    }

    echo '<div class="form-group"><label for="' . $name . '">' . $label
       . (!empty($config['required']) ? ' *' : '') . '</label>';

    switch ($config['type']) {
        case 'textarea':
            echo '<textarea id="' . $name . '" name="' . $name . '" ' . $required . '>'
               . htmlspecialchars($value) . '</textarea>';
            break;

        case 'select':
            echo '<select id="' . $name . '" name="' . $name . '" ' . $required . '>';
            foreach ($config['options'] as $opt) {
                $sel = ($opt === $value) ? 'selected' : '';
                echo '<option value="' . htmlspecialchars($opt) . '" ' . $sel . '>' . htmlspecialchars($opt) . '</option>';
            }
            echo '</select>';
            break;

        case 'event_select':
            $events = $pdo->query('SELECT event_id, event_name FROM events ORDER BY start_date DESC')->fetchAll();
            echo '<select id="' . $name . '" name="' . $name . '" ' . $required . '>';
            echo '<option value="">-- Select Event --</option>';
            foreach ($events as $ev) {
                $sel = ((string)$ev['event_id'] === (string)$value) ? 'selected' : '';
                echo '<option value="' . $ev['event_id'] . '" ' . $sel . '>' . htmlspecialchars($ev['event_name']) . '</option>';
            }
            echo '</select>';
            break;

        case 'number':
            echo '<input type="number" step="0.01" id="' . $name . '" name="' . $name . '" value="'
               . htmlspecialchars($value) . '" ' . $required . '>';
            break;

        case 'date':
            echo '<input type="date" id="' . $name . '" name="' . $name . '" value="'
               . htmlspecialchars($value) . '"' . $minAttr . ' ' . $required . '>';
            break;

        case 'time':
            echo '<input type="time" id="' . $name . '" name="' . $name . '" value="'
               . htmlspecialchars(substr($value, 0, 5)) . '" ' . $required . '>';
            break;

        case 'datetime':
            $v = $value ? date('Y-m-d\TH:i', strtotime($value)) : '';
            echo '<input type="datetime-local" id="' . $name . '" name="' . $name . '" value="'
               . htmlspecialchars($v) . '" ' . $required . '>';
            break;

        default: // text
            echo '<input type="text" id="' . $name . '" name="' . $name . '" value="'
               . htmlspecialchars($value) . '" ' . $required . '>';
    }

    echo '</div>';
}

/** Renders a status/type value as a colored badge pill */
function renderBadge($value): string
{
    if ($value === null || $value === '') return '';
    $safe = htmlspecialchars($value);
    $cls  = 'badge-' . preg_replace('/[^A-Za-z]/', '', $value);
    return '<span class="badge ' . $cls . '">' . $safe . '</span>';
}
