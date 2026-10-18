import re
import os

path = os.path.expanduser('~/ucp/resources/views/appointments/calendar.blade.php')
with open(path, 'r', encoding='utf-8') as f:
    c = f.read()

# 1. Header-Buttons zu einem Button "Eintrag erfassen" bündeln
btn_pattern = r"(@if\(auth\(\)->user\(\)->canDo\('manage_absences'\)\)[\s\S]*?Neuer Dienst<\/span>[\s\S]*?@endif)"
unified_btn = """@if(auth()->user()->canDo('edit_appointments') || auth()->user()->canDo('manage_absences'))
                    <button type="button" onclick="openCreateEntryModal('appointment')" class="h-8 inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white px-3 rounded-lg text-xs font-semibold shadow-sm transition gap-1.5">
                        <span>➕</span> <span>Eintrag erfassen</span>
                    </button>
                @endif"""
c = re.sub(btn_pattern, unified_btn, c, count=1)

# 2. Formulare auslesen
m_app = re.search(r"(<form action=\"\{\{\s*route\('appointments\.store'\)[\s\S]*?<\/form>)", c)
m_abs = re.search(r"(<form action=\"\{\{\s*route\('absences\.store'\)[\s\S]*?<\/form>)", c)

if m_app and m_abs:
    form_app = m_app.group(1)
    form_abs = m_abs.group(1)

    # Schließen-Buttons in Formularen auf closeCreateEntryModal() umbiegen
    form_app = form_app.replace("document.getElementById('newAppointmentModal').classList.add('hidden')", "closeCreateEntryModal()")
    form_abs = form_abs.replace("document.getElementById('absenceModal').classList.add('hidden')", "closeCreateEntryModal()")

    # Alte Modale entfernen
    c = re.sub(r"<!-- Modal: Neuen Bahndienst anlegen -->[\s\S]*?@endif\s*<!-- Modal: Termin Details & Bearbeiten -->", "<!-- Modal: Termin Details & Bearbeiten -->", c)
    c = re.sub(r"<!-- Modal: Abwesenheit erfassen -->[\s\S]*?@endif\s*<!-- Modal: Abwesenheit bearbeiten / ansehen -->", "<!-- Modal: Abwesenheit bearbeiten / ansehen -->", c)

    unified_modal = f"""<!-- Kombiniertes Erstell-Modal (Tabs) -->
    <div id="createEntryModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50 flex items-center justify-center p-3">
        <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full p-5 relative">
            <div class="flex justify-between items-center pb-2 border-b">
                <div class="flex gap-2">
                    @if(auth()->user()->canDo('edit_appointments'))
                        <button type="button" id="tabBtnApp" onclick="switchCreateTab('appointment')" class="px-3 py-1 text-xs font-bold rounded-lg bg-blue-600 text-white transition">
                            🚂 Neuer Dienst
                        </button>
                    @endif
                    @if(auth()->user()->canDo('manage_absences'))
                        <button type="button" id="tabBtnAbs" onclick="switchCreateTab('absence')" class="px-3 py-1 text-xs font-bold rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition">
                            ⛔ Abwesenheit
                        </button>
                    @endif
                </div>
                <button type="button" onclick="closeCreateEntryModal()" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
            </div>

            @if(auth()->user()->canDo('edit_appointments'))
                <div id="tabContentApp">
                    {form_app}
                </div>
            @endif

            @if(auth()->user()->canDo('manage_absences'))
                <div id="tabContentAbs" class="hidden">
                    {form_abs}
                </div>
            @endif
        </div>
    </div>
"""
    c = c.replace("<!-- Modal: Termin Details & Bearbeiten -->", unified_modal + "\n    <!-- Modal: Termin Details & Bearbeiten -->")

# 3. JavaScript für die Tabs und das Öffnen/Schließen
js_tabs = """        function openCreateEntryModal(defaultTab = 'appointment') {
            const m = document.getElementById('createEntryModal');
            if (!m) return;
            switchCreateTab(defaultTab);
            m.classList.remove('hidden');
        }

        function closeCreateEntryModal() {
            const m = document.getElementById('createEntryModal');
            if (m) m.classList.add('hidden');
        }

        function switchCreateTab(tab) {
            const tabApp = document.getElementById('tabContentApp');
            const tabAbs = document.getElementById('tabContentAbs');
            const btnApp = document.getElementById('tabBtnApp');
            const btnAbs = document.getElementById('tabBtnAbs');

            if (tab === 'absence' && tabAbs) {
                if (tabApp) tabApp.classList.add('hidden');
                tabAbs.classList.remove('hidden');

                if (btnApp) {
                    btnApp.className = "px-3 py-1 text-xs font-bold rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition";
                }
                if (btnAbs) {
                    btnAbs.className = "px-3 py-1 text-xs font-bold rounded-lg bg-yellow-600 text-white transition";
                }
            } else if (tabApp) {
                tabApp.classList.remove('hidden');
                if (tabAbs) tabAbs.classList.add('hidden');

                if (btnApp) {
                    btnApp.className = "px-3 py-1 text-xs font-bold rounded-lg bg-blue-600 text-white transition";
                }
                if (btnAbs) {
                    btnAbs.className = "px-3 py-1 text-xs font-bold rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition";
                }
            }
        }
"""

if "function switchCreateTab" not in c:
    c = c.replace("function deleteCurrentAppointment()", js_tabs + "\n        function deleteCurrentAppointment()")

# 4. openAbsenceModal direkt auf den Tab weiterleiten
c = re.sub(
    r"function openAbsenceModal\(\)\s*\{[\s\S]*?document\.getElementById\('absenceModal'\)[\s\S]*?\}",
    "function openAbsenceModal() { openCreateEntryModal('absence'); }",
    c
)

with open(path, 'w', encoding='utf-8') as f:
    f.write(c)

print("Kombiniertes Tab-Modal fehlerfrei eingebaut!")
