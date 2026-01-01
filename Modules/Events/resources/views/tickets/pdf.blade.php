<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Ticket - {{ $attendee->reference_no }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            background-color: #f3f4f6;
            padding: 20px;
        }

        .ticket {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            overflow: hidden;
            max-width: 700px;
            margin: 0 auto;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }

        .ticket-header {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px 30px;
            border-bottom: 2px dashed rgba(255, 255, 255, 0.3);
        }

        .ticket-header h1 {
            color: white;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .ticket-header p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
        }

        .ticket-body {
            display: table;
            width: 100%;
            padding: 20px;
        }

        .ticket-info {
            display: table-cell;
            width: 60%;
            vertical-align: top;
            padding-right: 20px;
        }

        .ticket-qr {
            display: table-cell;
            width: 40%;
            text-align: center;
            vertical-align: middle;
            background: white;
            border-radius: 12px;
            padding: 15px;
        }

        .info-row {
            margin-bottom: 15px;
        }

        .info-label {
            color: rgba(255, 255, 255, 0.7);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }

        .info-value {
            color: white;
            font-size: 16px;
            font-weight: 600;
        }

        .info-value.large {
            font-size: 20px;
        }

        .qr-code img {
            max-width: 140px;
            height: auto;
        }

        .reference {
            font-family: monospace;
            font-size: 14px;
            color: #333;
            margin-top: 10px;
            background: #f0f0f0;
            padding: 5px 10px;
            border-radius: 4px;
        }

        .ticket-footer {
            background: rgba(0, 0, 0, 0.1);
            padding: 15px 30px;
            text-align: center;
            color: rgba(255, 255, 255, 0.8);
            font-size: 12px;
        }

        .ticket-type-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .divider {
            border-top: 2px dashed rgba(255, 255, 255, 0.3);
            margin: 0 30px;
        }
    </style>
</head>
<body>
    <div class="ticket">
        {{-- Header --}}
        <div class="ticket-header">
            <h1>{{ $event->title }}</h1>
            <p>{{ $event->start_date->format('l, F j, Y') }} at {{ $event->start_date->format('g:i A') }}</p>
        </div>

        {{-- Body --}}
        <div class="ticket-body">
            <div class="ticket-info">
                <div class="info-row">
                    <div class="info-label">Attendee</div>
                    <div class="info-value large">{{ $attendee->display_name }}</div>
                </div>

                @if($attendee->company_name)
                <div class="info-row">
                    <div class="info-label">Company</div>
                    <div class="info-value">{{ $attendee->company_name }}</div>
                </div>
                @endif

                <div class="info-row">
                    <div class="info-label">Ticket Type</div>
                    <div class="info-value">
                        <span class="ticket-type-badge">{{ $ticketType->name }}</span>
                    </div>
                </div>

                <div class="info-row">
                    <div class="info-label">Venue</div>
                    <div class="info-value">
                        @if($event->location_type->value === 'physical')
                            {{ $event->location_name ?? 'TBA' }}
                            @if($event->location_address)
                                <br><span style="font-size: 12px; color: rgba(255,255,255,0.7);">{{ $event->location_address }}</span>
                            @endif
                        @elseif($event->location_type->value === 'online')
                            Online Event
                        @else
                            {{ $event->location_name ?? 'Online + Physical' }}
                        @endif
                    </div>
                </div>

                @if($event->end_date && $event->end_date->format('Y-m-d') !== $event->start_date->format('Y-m-d'))
                <div class="info-row">
                    <div class="info-label">End Date</div>
                    <div class="info-value">{{ $event->end_date->format('l, F j, Y \a\t g:i A') }}</div>
                </div>
                @endif
            </div>

            <div class="ticket-qr">
                <div class="qr-code">
                    <img src="data:image/svg+xml;base64,{{ $qrCode }}" alt="{{ __('QR Code') }}">
                </div>
                <div class="reference">{{ $attendee->reference_no }}</div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="ticket-footer">
            <p>{{ __('Present this QR code at the venue for check-in') }}</p>
            <p style="margin-top: 5px; font-size: 10px;">
                {{ __('This ticket is non-transferable. Valid only for the named attendee.') }}
            </p>
        </div>
    </div>
</body>
</html>
