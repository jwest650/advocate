<?php

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use App\Models\NotificationTemplateLang;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $supportedLanguages = ['en', 'es', 'ar', 'da', 'de', 'fr', 'he', 'it', 'ja', 'nl', 'pl', 'pt', 'pt-BR', 'ru', 'tr', 'zh'];
        $langCodes = $supportedLanguages;

        $templates = [
            [
                'name' => 'New Case',
                'type' => 'slack',
                'translations' => [
                    'en' => [
                        'title' => 'New Case Created: {case_number}',
                        'content' => 'A new case "{case_number}" has been created for client {client_name}. Case type: {case_type}. Created by: {created_by}.'
                    ],
                    'es' => [
                        'title' => 'Nuevo caso creado: {case_number}',
                        'content' => 'Se ha creado un nuevo caso "{case_number}" para el cliente {client_name}. Tipo de caso: {case_type}. Creado por: {created_by}.'
                    ],
                    'ar' => [
                        'title' => 'تم إنشاء قضية جديدة: {case_number}',
                        'content' => 'تم إنشاء قضية جديدة "{case_number}" للعميل {client_name}. نوع القضية: {case_type}. تم الإنشاء بواسطة: {created_by}.'
                    ],
                    'da' => [
                        'title' => 'Ny sag oprettet: {case_number}',
                        'content' => 'En ny sag "{case_number}" er blevet oprettet for klienten {client_name}. Sagstype: {case_type}. Oprettet af: {created_by}.'
                    ],
                    'de' => [
                        'title' => 'Neuer Fall erstellt: {case_number}',
                        'content' => 'Ein neuer Fall "{case_number}" wurde für den Kunden {client_name} erstellt. Falltyp: {case_type}. Erstellt von: {created_by}.'
                    ],
                    'fr' => [
                        'title' => 'Nouveau dossier créé : {case_number}',
                        'content' => 'Un nouveau dossier "{case_number}" a été créé pour le client {client_name}. Type de dossier : {case_type}. Créé par : {created_by}.'
                    ],
                    'he' => [
                        'title' => 'נוצר תיק חדש: {case_number}',
                        'content' => 'נוצר תיק חדש "{case_number}" עבור הלקוח {client_name}. סוג תיק: {case_type}. נוצר על ידי: {created_by}.'
                    ],
                    'it' => [
                        'title' => 'Nuovo caso creato: {case_number}',
                        'content' => 'È stato creato un nuovo caso "{case_number}" per il cliente {client_name}. Tipo di caso: {case_type}. Creato da: {created_by}.'
                    ],
                    'ja' => [
                        'title' => '新しい案件が作成されました: {case_number}',
                        'content' => 'クライアント {client_name} のために新しい案件「{case_number}」が作成されました。案件の種類: {case_type}。作成者: {created_by}。'
                    ],
                    'nl' => [
                        'title' => 'Nieuwe zaak aangemaakt: {case_number}',
                        'content' => 'Er is een nieuwe zaak "{case_number}" aangemaakt voor klant {client_name}. Zaaktype: {case_type}. Aangemaakt door: {created_by}.'
                    ],
                    'pl' => [
                        'title' => 'Nowa sprawa utworzona: {case_number}',
                        'content' => 'Utworzono nową sprawę "{case_number}" dla klienta {client_name}. Typ sprawy: {case_type}. Utworzył: {created_by}.'
                    ],
                    'pt' => [
                        'title' => 'Novo caso criado: {case_number}',
                        'content' => 'Um novo caso "{case_number}" foi criado para o cliente {client_name}. Tipo de caso: {case_type}. Criado por: {created_by}.'
                    ],
                    'pt-BR' => [
                        'title' => 'Novo caso criado: {case_number}',
                        'content' => 'Um novo caso "{case_number}" foi criado para o cliente {client_name}. Tipo de caso: {case_type}. Criado por: {created_by}.'
                    ],
                    'ru' => [
                        'title' => 'Создано новое дело: {case_number}',
                        'content' => 'Создано новое дело "{case_number}" для клиента {client_name}. Тип дела: {case_type}. Создано пользователем: {created_by}.'
                    ],
                    'tr' => [
                        'title' => 'Yeni Dava Oluşturuldu: {case_number}',
                        'content' => '"{case_number}" adlı yeni dava, {client_name} müşterisi için oluşturuldu. Dava türü: {case_type}. Oluşturan: {created_by}.'
                    ],
                    'zh' => [
                        'title' => '已创建新案件: {case_number}',
                        'content' => '为客户 {client_name} 创建了新案件“{case_number}”。案件类型: {case_type}。创建者: {created_by}。'
                    ],
                ]
            ],

            [
                'name' => 'New Client',
                'type' => 'slack',
                'translations' => [
                    'en' => [
                        'title' => 'New Client Added: {client_name}',
                        'content' => 'A new client "{client_name}" has been added to the system. Client type: {client_type}. Email: {email}.'
                    ],
                    'es' => [
                        'title' => 'Nuevo cliente agregado: {client_name}',
                        'content' => 'Se ha agregado un nuevo cliente "{client_name}" al sistema. Tipo de cliente: {client_type}. Correo electrónico: {email}.'
                    ],
                    'ar' => [
                        'title' => 'تمت إضافة عميل جديد: {client_name}',
                        'content' => 'تمت إضافة عميل جديد "{client_name}" إلى النظام. نوع العميل: {client_type}. البريد الإلكتروني: {email}.'
                    ],
                    'da' => [
                        'title' => 'Ny kunde tilføjet: {client_name}',
                        'content' => 'En ny kunde "{client_name}" er blevet tilføjet til systemet. Kundetype: {client_type}. E-mail: {email}.'
                    ],
                    'de' => [
                        'title' => 'Neuer Kunde hinzugefügt: {client_name}',
                        'content' => 'Ein neuer Kunde "{client_name}" wurde dem System hinzugefügt. Kundentyp: {client_type}. E-Mail: {email}.'
                    ],
                    'fr' => [
                        'title' => 'Nouveau client ajouté : {client_name}',
                        'content' => 'Un nouveau client "{client_name}" a été ajouté au système. Type de client : {client_type}. E-mail : {email}.'
                    ],
                    'he' => [
                        'title' => 'נוסף לקוח חדש: {client_name}',
                        'content' => 'נוסף לקוח חדש "{client_name}" למערכת. סוג לקוח: {client_type}. דוא"ל: {email}.'
                    ],
                    'it' => [
                        'title' => 'Nuovo cliente aggiunto: {client_name}',
                        'content' => 'È stato aggiunto un nuovo cliente "{client_name}" al sistema. Tipo di cliente: {client_type}. Email: {email}.'
                    ],
                    'ja' => [
                        'title' => '新しいクライアントが追加されました: {client_name}',
                        'content' => '新しいクライアント「{client_name}」がシステムに追加されました。クライアントの種類: {client_type}。メール: {email}。'
                    ],
                    'nl' => [
                        'title' => 'Nieuwe klant toegevoegd: {client_name}',
                        'content' => 'Een nieuwe klant "{client_name}" is toegevoegd aan het systeem. Klanttype: {client_type}. E-mail: {email}.'
                    ],
                    'pl' => [
                        'title' => 'Dodano nowego klienta: {client_name}',
                        'content' => 'Nowy klient "{client_name}" został dodany do systemu. Typ klienta: {client_type}. E-mail: {email}.'
                    ],
                    'pt' => [
                        'title' => 'Novo cliente adicionado: {client_name}',
                        'content' => 'Um novo cliente "{client_name}" foi adicionado ao sistema. Tipo de cliente: {client_type}. E-mail: {email}.'
                    ],
                    'pt-BR' => [
                        'title' => 'Novo cliente adicionado: {client_name}',
                        'content' => 'Um novo cliente "{client_name}" foi adicionado ao sistema. Tipo de cliente: {client_type}. E-mail: {email}.'
                    ],
                    'ru' => [
                        'title' => 'Добавлен новый клиент: {client_name}',
                        'content' => 'В систему добавлен новый клиент "{client_name}". Тип клиента: {client_type}. Электронная почта: {email}.'
                    ],
                    'tr' => [
                        'title' => 'Yeni Müşteri Eklendi: {client_name}',
                        'content' => 'Sisteme yeni bir müşteri "{client_name}" eklendi. Müşteri türü: {client_type}. E-posta: {email}.'
                    ],
                    'zh' => [
                        'title' => '已添加新客户: {client_name}',
                        'content' => '系统中已添加新客户“{client_name}”。客户类型: {client_type}。电子邮件: {email}。'
                    ],
                ]
            ],
            [
                'name' => 'New Task',
                'type' => 'slack',
                'translations' => [
                    'en' => [
                        'title' => 'New Task Assigned: {task_title}',
                        'content' => 'You have been assigned a new task "{task_title}". Priority: {priority}. Due date: {due_date}. Assigned to: {assigned_to}.'
                    ],
                    'es' => [
                        'title' => 'Nueva tarea asignada: {task_title}',
                        'content' => 'Se te ha asignado una nueva tarea "{task_title}". Prioridad: {priority}. Fecha de vencimiento: {due_date}. Asignado a: {assigned_to}.'
                    ],
                    'ar' => [
                        'title' => 'تم تعيين مهمة جديدة: {task_title}',
                        'content' => 'تم تعيين مهمة جديدة لك بعنوان "{task_title}". الأولوية: {priority}. تاريخ الاستحقاق: {due_date}. تم التعيين إلى: {assigned_to}.'
                    ],
                    'da' => [
                        'title' => 'Ny opgave tildelt: {task_title}',
                        'content' => 'Du er blevet tildelt en ny opgave "{task_title}". Prioritet: {priority}. Forfaldsdato: {due_date}. Tildelt til: {assigned_to}.'
                    ],
                    'de' => [
                        'title' => 'Neue Aufgabe zugewiesen: {task_title}',
                        'content' => 'Ihnen wurde eine neue Aufgabe "{task_title}" zugewiesen. Priorität: {priority}. Fälligkeitsdatum: {due_date}. Zugewiesen an: {assigned_to}.'
                    ],
                    'fr' => [
                        'title' => 'Nouvelle tâche attribuée : {task_title}',
                        'content' => 'Une nouvelle tâche "{task_title}" vous a été attribuée. Priorité : {priority}. Date d\'échéance : {due_date}. Assigné à : {assigned_to}.'
                    ],
                    'he' => [
                        'title' => 'הוקצתה משימה חדשה: {task_title}',
                        'content' => 'הוקצתה לך משימה חדשה "{task_title}". עדיפות: {priority}. תאריך יעד: {due_date}. הוקצה ל: {assigned_to}.'
                    ],
                    'it' => [
                        'title' => 'Nuovo compito assegnato: {task_title}',
                        'content' => 'Ti è stato assegnato un nuovo compito "{task_title}". Priorità: {priority}. Scadenza: {due_date}. Assegnato a: {assigned_to}.'
                    ],
                    'ja' => [
                        'title' => '新しいタスクが割り当てられました: {task_title}',
                        'content' => 'あなたに新しいタスク「{task_title}」が割り当てられました。優先度: {priority}。期限: {due_date}。担当者: {assigned_to}。'
                    ],
                    'nl' => [
                        'title' => 'Nieuwe taak toegewezen: {task_title}',
                        'content' => 'Je hebt een nieuwe taak "{task_title}" toegewezen gekregen. Prioriteit: {priority}. Vervaldatum: {due_date}. Toegewezen aan: {assigned_to}.'
                    ],
                    'pl' => [
                        'title' => 'Nowe zadanie przydzielone: {task_title}',
                        'content' => 'Przydzielono Ci nowe zadanie "{task_title}". Priorytet: {priority}. Termin: {due_date}. Przydzielono do: {assigned_to}.'
                    ],
                    'pt' => [
                        'title' => 'Nova tarefa atribuída: {task_title}',
                        'content' => 'Você recebeu uma nova tarefa "{task_title}". Prioridade: {priority}. Data de vencimento: {due_date}. Atribuído a: {assigned_to}.'
                    ],
                    'pt-BR' => [
                        'title' => 'Nova tarefa atribuída: {task_title}',
                        'content' => 'Você recebeu uma nova tarefa "{task_title}". Prioridade: {priority}. Data de vencimento: {due_date}. Atribuído a: {assigned_to}.'
                    ],
                    'ru' => [
                        'title' => 'Назначена новая задача: {task_title}',
                        'content' => 'Вам назначена новая задача "{task_title}". Приоритет: {priority}. Срок: {due_date}. Назначено на: {assigned_to}.'
                    ],
                    'tr' => [
                        'title' => 'Yeni Görev Atandı: {task_title}',
                        'content' => 'Size yeni bir görev "{task_title}" atandı. Öncelik: {priority}. Bitiş tarihi: {due_date}. Atanan kişi: {assigned_to}.'
                    ],
                    'zh' => [
                        'title' => '已分配新任务: {task_title}',
                        'content' => '您已被分配新任务“{task_title}”。优先级: {priority}。截止日期: {due_date}。分配给: {assigned_to}。'
                    ],
                ]
            ],

            [
                'name' => 'New Hearing',
                'type' => 'slack',
                'translations' => [
                    'en' => [
                        'title' => 'New Hearing Scheduled: {case_number}',
                        'content' => 'A new hearing has been scheduled for case {case_number}. Date: {hearing_date}. Court: {court}. Judge: {judge}.'
                    ],
                    'es' => [
                        'title' => 'Nueva audiencia programada: {case_number}',
                        'content' => 'Se ha programado una nueva audiencia para el caso {case_number}. Fecha: {hearing_date}. Tribunal: {court}. Juez: {judge}.'
                    ],
                    'ar' => [
                        'title' => 'تم تحديد جلسة جديدة: {case_number}',
                        'content' => 'تم تحديد جلسة جديدة للقضية {case_number}. التاريخ: {hearing_date}. المحكمة: {court}. القاضي: {judge}.'
                    ],
                    'da' => [
                        'title' => 'Ny høring planlagt: {case_number}',
                        'content' => 'En ny høring er blevet planlagt for sag {case_number}. Dato: {hearing_date}. Ret: {court}. Dommer: {judge}.'
                    ],
                    'de' => [
                        'title' => 'Neue Anhörung angesetzt: {case_number}',
                        'content' => 'Eine neue Anhörung wurde für den Fall {case_number} angesetzt. Datum: {hearing_date}. Gericht: {court}. Richter: {judge}.'
                    ],
                    'fr' => [
                        'title' => 'Nouvelle audience programmée : {case_number}',
                        'content' => 'Une nouvelle audience a été programmée pour le dossier {case_number}. Date : {hearing_date}. Tribunal : {court}. Juge : {judge}.'
                    ],
                    'he' => [
                        'title' => 'נקבע דיון חדש: {case_number}',
                        'content' => 'נקבע דיון חדש בתיק {case_number}. תאריך: {hearing_date}. בית משפט: {court}. שופט: {judge}.'
                    ],
                    'it' => [
                        'title' => 'Nuova udienza programmata: {case_number}',
                        'content' => 'È stata programmata una nuova udienza per il caso {case_number}. Data: {hearing_date}. Tribunale: {court}. Giudice: {judge}.'
                    ],
                    'ja' => [
                        'title' => '新しい公聴会が予定されました: {case_number}',
                        'content' => '事件 {case_number} の新しい公聴会が予定されました。日付: {hearing_date}。裁判所: {court}。裁判官: {judge}。'
                    ],
                    'nl' => [
                        'title' => 'Nieuwe zitting gepland: {case_number}',
                        'content' => 'Er is een nieuwe zitting gepland voor zaak {case_number}. Datum: {hearing_date}. Rechtbank: {court}. Rechter: {judge}.'
                    ],
                    'pl' => [
                        'title' => 'Wyznaczono nową rozprawę: {case_number}',
                        'content' => 'Wyznaczono nową rozprawę dla sprawy {case_number}. Data: {hearing_date}. Sąd: {court}. Sędzia: {judge}.'
                    ],
                    'pt' => [
                        'title' => 'Nova audiência agendada: {case_number}',
                        'content' => 'Uma nova audiência foi agendada para o caso {case_number}. Data: {hearing_date}. Tribunal: {court}. Juiz: {judge}.'
                    ],
                    'pt-BR' => [
                        'title' => 'Nova audiência agendada: {case_number}',
                        'content' => 'Uma nova audiência foi agendada para o caso {case_number}. Data: {hearing_date}. Tribunal: {court}. Juiz: {judge}.'
                    ],
                    'ru' => [
                        'title' => 'Назначено новое заседание: {case_number}',
                        'content' => 'Назначено новое судебное заседание по делу {case_number}. Дата: {hearing_date}. Суд: {court}. Судья: {judge}.'
                    ],
                    'tr' => [
                        'title' => 'Yeni Duruşma Planlandı: {case_number}',
                        'content' => '{case_number} numaralı dava için yeni bir duruşma planlandı. Tarih: {hearing_date}. Mahkeme: {court}. Hakim: {judge}.'
                    ],
                    'zh' => [
                        'title' => '已安排新听证会: {case_number}',
                        'content' => '案件 {case_number} 已安排新听证会。日期: {hearing_date}。法院: {court}。法官: {judge}。'
                    ],
                ]
            ],

            [
                'name' => 'New Invoice',
                'type' => 'slack',
                'translations' => [
                    'en' => [
                        'title' => 'New Invoice Created: {invoice_number}',
                        'content' => 'A new invoice {invoice_number} has been created for {client_name}. Amount: {amount}. Due date: {due_date}.'
                    ],
                    'es' => [
                        'title' => 'Nueva factura creada: {invoice_number}',
                        'content' => 'Se ha creado una nueva factura {invoice_number} para {client_name}. Importe: {amount}. Fecha de vencimiento: {due_date}.'
                    ],
                    'ar' => [
                        'title' => 'تم إنشاء فاتورة جديدة: {invoice_number}',
                        'content' => 'تم إنشاء فاتورة جديدة {invoice_number} للعميل {client_name}. المبلغ: {amount}. تاريخ الاستحقاق: {due_date}.'
                    ],
                    'da' => [
                        'title' => 'Ny faktura oprettet: {invoice_number}',
                        'content' => 'En ny faktura {invoice_number} er blevet oprettet for {client_name}. Beløb: {amount}. Forfaldsdato: {due_date}.'
                    ],
                    'de' => [
                        'title' => 'Neue Rechnung erstellt: {invoice_number}',
                        'content' => 'Eine neue Rechnung {invoice_number} wurde für {client_name} erstellt. Betrag: {amount}. Fälligkeitsdatum: {due_date}.'
                    ],
                    'fr' => [
                        'title' => 'Nouvelle facture créée : {invoice_number}',
                        'content' => 'Une nouvelle facture {invoice_number} a été créée pour {client_name}. Montant : {amount}. Date d’échéance : {due_date}.'
                    ],
                    'he' => [
                        'title' => 'נוצרה חשבונית חדשה: {invoice_number}',
                        'content' => 'נוצרה חשבונית חדשה {invoice_number} עבור {client_name}. סכום: {amount}. תאריך יעד: {due_date}.'
                    ],
                    'it' => [
                        'title' => 'Nuova fattura creata: {invoice_number}',
                        'content' => 'È stata creata una nuova fattura {invoice_number} per {client_name}. Importo: {amount}. Scadenza: {due_date}.'
                    ],
                    'ja' => [
                        'title' => '新しい請求書が作成されました: {invoice_number}',
                        'content' => '{client_name} のために新しい請求書 {invoice_number} が作成されました。金額: {amount}。期限: {due_date}。'
                    ],
                    'nl' => [
                        'title' => 'Nieuwe factuur aangemaakt: {invoice_number}',
                        'content' => 'Een nieuwe factuur {invoice_number} is aangemaakt voor {client_name}. Bedrag: {amount}. Vervaldatum: {due_date}.'
                    ],
                    'pl' => [
                        'title' => 'Utworzono nową fakturę: {invoice_number}',
                        'content' => 'Utworzono nową fakturę {invoice_number} dla klienta {client_name}. Kwota: {amount}. Termin płatności: {due_date}.'
                    ],
                    'pt' => [
                        'title' => 'Nova fatura criada: {invoice_number}',
                        'content' => 'Uma nova fatura {invoice_number} foi criada para {client_name}. Valor: {amount}. Data de vencimento: {due_date}.'
                    ],
                    'pt-BR' => [
                        'title' => 'Nova fatura criada: {invoice_number}',
                        'content' => 'Uma nova fatura {invoice_number} foi criada para {client_name}. Valor: {amount}. Data de vencimento: {due_date}.'
                    ],
                    'ru' => [
                        'title' => 'Создан новый счет: {invoice_number}',
                        'content' => 'Создан новый счет {invoice_number} для клиента {client_name}. Сумма: {amount}. Срок оплаты: {due_date}.'
                    ],
                    'tr' => [
                        'title' => 'Yeni Fatura Oluşturuldu: {invoice_number}',
                        'content' => '{client_name} için yeni bir fatura {invoice_number} oluşturuldu. Tutar: {amount}. Son ödeme tarihi: {due_date}.'
                    ],
                    'zh' => [
                        'title' => '已创建新发票: {invoice_number}',
                        'content' => '为客户 {client_name} 创建了新发票 {invoice_number}。金额: {amount}。到期日: {due_date}。'
                    ],
                ]
            ],
            [
                'name' => 'Invoice Sent',
                'type' => 'slack',
                'translations' => [
                    'en' => [
                        'title' => 'Invoice Sent: {invoice_number}',
                        'content' => 'Invoice {invoice_number} has been sent to {client_name}. Amount: {amount}. Sent on: {sent_date}.'
                    ],
                    'es' => [
                        'title' => 'Factura enviada: {invoice_number}',
                        'content' => 'La factura {invoice_number} ha sido enviada a {client_name}. Importe: {amount}. Enviada el: {sent_date}.'
                    ],
                    'ar' => [
                        'title' => 'تم إرسال الفاتورة: {invoice_number}',
                        'content' => 'تم إرسال الفاتورة {invoice_number} إلى {client_name}. المبلغ: {amount}. تم الإرسال في: {sent_date}.'
                    ],
                    'da' => [
                        'title' => 'Faktura sendt: {invoice_number}',
                        'content' => 'Faktura {invoice_number} er sendt til {client_name}. Beløb: {amount}. Sendt den: {sent_date}.'
                    ],
                    'de' => [
                        'title' => 'Rechnung gesendet: {invoice_number}',
                        'content' => 'Rechnung {invoice_number} wurde an {client_name} gesendet. Betrag: {amount}. Gesendet am: {sent_date}.'
                    ],
                    'fr' => [
                        'title' => 'Facture envoyée : {invoice_number}',
                        'content' => 'La facture {invoice_number} a été envoyée à {client_name}. Montant : {amount}. Envoyée le : {sent_date}.'
                    ],
                    'he' => [
                        'title' => 'החשבונית נשלחה: {invoice_number}',
                        'content' => 'החשבונית {invoice_number} נשלחה אל {client_name}. סכום: {amount}. נשלחה בתאריך: {sent_date}.'
                    ],
                    'it' => [
                        'title' => 'Fattura inviata: {invoice_number}',
                        'content' => 'La fattura {invoice_number} è stata inviata a {client_name}. Importo: {amount}. Inviata il: {sent_date}.'
                    ],
                    'ja' => [
                        'title' => '請求書が送信されました: {invoice_number}',
                        'content' => '請求書 {invoice_number} が {client_name} に送信されました。金額: {amount}。送信日: {sent_date}。'
                    ],
                    'nl' => [
                        'title' => 'Factuur verzonden: {invoice_number}',
                        'content' => 'Factuur {invoice_number} is verzonden naar {client_name}. Bedrag: {amount}. Verzonden op: {sent_date}.'
                    ],
                    'pl' => [
                        'title' => 'Wysłano fakturę: {invoice_number}',
                        'content' => 'Faktura {invoice_number} została wysłana do {client_name}. Kwota: {amount}. Wysłano dnia: {sent_date}.'
                    ],
                    'pt' => [
                        'title' => 'Fatura enviada: {invoice_number}',
                        'content' => 'A fatura {invoice_number} foi enviada para {client_name}. Valor: {amount}. Enviada em: {sent_date}.'
                    ],
                    'pt-BR' => [
                        'title' => 'Fatura enviada: {invoice_number}',
                        'content' => 'A fatura {invoice_number} foi enviada para {client_name}. Valor: {amount}. Enviada em: {sent_date}.'
                    ],
                    'ru' => [
                        'title' => 'Счет отправлен: {invoice_number}',
                        'content' => 'Счет {invoice_number} отправлен клиенту {client_name}. Сумма: {amount}. Отправлено: {sent_date}.'
                    ],
                    'tr' => [
                        'title' => 'Fatura Gönderildi: {invoice_number}',
                        'content' => '{invoice_number} numaralı fatura {client_name} müşterisine gönderildi. Tutar: {amount}. Gönderim tarihi: {sent_date}.'
                    ],
                    'zh' => [
                        'title' => '发票已发送: {invoice_number}',
                        'content' => '发票 {invoice_number} 已发送给 {client_name}。金额: {amount}。发送日期: {sent_date}。'
                    ],
                ]
            ],

            [
                'name' => 'New Court',
                'type' => 'slack',
                'translations' => [
                    'en' => [
                        'title' => 'New Court Added: {court_name}',
                        'content' => 'A new court "{court_name}" has been added to the system. Type: {court_type}. Location: {location}.'
                    ],
                    'es' => [
                        'title' => 'Nuevo tribunal agregado: {court_name}',
                        'content' => 'Se ha agregado un nuevo tribunal "{court_name}" al sistema. Tipo: {court_type}. Ubicación: {location}.'
                    ],
                    'ar' => [
                        'title' => 'تمت إضافة محكمة جديدة: {court_name}',
                        'content' => 'تمت إضافة محكمة جديدة "{court_name}" إلى النظام. النوع: {court_type}. الموقع: {location}.'
                    ],
                    'da' => [
                        'title' => 'Ny domstol tilføjet: {court_name}',
                        'content' => 'En ny domstol "{court_name}" er blevet tilføjet til systemet. Type: {court_type}. Placering: {location}.'
                    ],
                    'de' => [
                        'title' => 'Neues Gericht hinzugefügt: {court_name}',
                        'content' => 'Ein neues Gericht "{court_name}" wurde dem System hinzugefügt. Typ: {court_type}. Standort: {location}.'
                    ],
                    'fr' => [
                        'title' => 'Nouveau tribunal ajouté : {court_name}',
                        'content' => 'Un nouveau tribunal "{court_name}" a été ajouté au système. Type : {court_type}. Emplacement : {location}.'
                    ],
                    'he' => [
                        'title' => 'בית משפט חדש נוסף: {court_name}',
                        'content' => 'בית משפט חדש "{court_name}" נוסף למערכת. סוג: {court_type}. מיקום: {location}.'
                    ],
                    'it' => [
                        'title' => 'Nuovo tribunale aggiunto: {court_name}',
                        'content' => 'È stato aggiunto un nuovo tribunale "{court_name}" al sistema. Tipo: {court_type}. Posizione: {location}.'
                    ],
                    'ja' => [
                        'title' => '新しい裁判所が追加されました: {court_name}',
                        'content' => '新しい裁判所「{court_name}」がシステムに追加されました。種類: {court_type}。場所: {location}。'
                    ],
                    'nl' => [
                        'title' => 'Nieuwe rechtbank toegevoegd: {court_name}',
                        'content' => 'Een nieuwe rechtbank "{court_name}" is toegevoegd aan het systeem. Type: {court_type}. Locatie: {location}.'
                    ],
                    'pl' => [
                        'title' => 'Dodano nowy sąd: {court_name}',
                        'content' => 'Nowy sąd "{court_name}" został dodany do systemu. Typ: {court_type}. Lokalizacja: {location}.'
                    ],
                    'pt' => [
                        'title' => 'Novo tribunal adicionado: {court_name}',
                        'content' => 'Um novo tribunal "{court_name}" foi adicionado ao sistema. Tipo: {court_type}. Localização: {location}.'
                    ],
                    'pt-BR' => [
                        'title' => 'Novo tribunal adicionado: {court_name}',
                        'content' => 'Um novo tribunal "{court_name}" foi adicionado ao sistema. Tipo: {court_type}. Localização: {location}.'
                    ],
                    'ru' => [
                        'title' => 'Добавлен новый суд: {court_name}',
                        'content' => 'Новый суд "{court_name}" был добавлен в систему. Тип: {court_type}. Местоположение: {location}.'
                    ],
                    'tr' => [
                        'title' => 'Yeni Mahkeme Eklendi: {court_name}',
                        'content' => 'Sisteme yeni bir mahkeme "{court_name}" eklendi. Tür: {court_type}. Konum: {location}.'
                    ],
                    'zh' => [
                        'title' => '已添加新法院: {court_name}',
                        'content' => '新法院 "{court_name}" 已添加到系统中。类型：{court_type}。位置：{location}。'
                    ],
                ]
            ],

            [
                'name' => 'New Judge',
                'type' => 'slack',
                'translations' => [
                    'en' => [
                        'title' => 'New Judge Added: {judge_name}',
                        'content' => 'A new judge "{judge_name}" has been added to the system. Court: {court}. Specialization: {specialization}.'
                    ],
                    'es' => [
                        'title' => 'Nuevo juez agregado: {judge_name}',
                        'content' => 'Se ha agregado un nuevo juez "{judge_name}" al sistema. Tribunal: {court}. Especialización: {specialization}.'
                    ],
                    'ar' => [
                        'title' => 'تمت إضافة قاضٍ جديد: {judge_name}',
                        'content' => 'تمت إضافة القاضي الجديد "{judge_name}" إلى النظام. المحكمة: {court}. التخصص: {specialization}.'
                    ],
                    'da' => [
                        'title' => 'Ny dommer tilføjet: {judge_name}',
                        'content' => 'En ny dommer "{judge_name}" er blevet tilføjet til systemet. Domstol: {court}. Speciale: {specialization}.'
                    ],
                    'de' => [
                        'title' => 'Neuer Richter hinzugefügt: {judge_name}',
                        'content' => 'Ein neuer Richter "{judge_name}" wurde dem System hinzugefügt. Gericht: {court}. Spezialisierung: {specialization}.'
                    ],
                    'fr' => [
                        'title' => 'Nouveau juge ajouté : {judge_name}',
                        'content' => 'Un nouveau juge "{judge_name}" a été ajouté au système. Tribunal : {court}. Spécialisation : {specialization}.'
                    ],
                    'he' => [
                        'title' => 'נוסף שופט חדש: {judge_name}',
                        'content' => 'השופט החדש "{judge_name}" נוסף למערכת. בית משפט: {court}. התמחות: {specialization}.'
                    ],
                    'it' => [
                        'title' => 'Nuovo giudice aggiunto: {judge_name}',
                        'content' => 'È stato aggiunto un nuovo giudice "{judge_name}" al sistema. Tribunale: {court}. Specializzazione: {specialization}.'
                    ],
                    'ja' => [
                        'title' => '新しい裁判官が追加されました: {judge_name}',
                        'content' => '新しい裁判官「{judge_name}」がシステムに追加されました。裁判所: {court}。専門分野: {specialization}。'
                    ],
                    'nl' => [
                        'title' => 'Nieuwe rechter toegevoegd: {judge_name}',
                        'content' => 'Een nieuwe rechter "{judge_name}" is toegevoegd aan het systeem. Rechtbank: {court}. Specialisatie: {specialization}.'
                    ],
                    'pl' => [
                        'title' => 'Dodano nowego sędziego: {judge_name}',
                        'content' => 'Nowy sędzia "{judge_name}" został dodany do systemu. Sąd: {court}. Specjalizacja: {specialization}.'
                    ],
                    'pt' => [
                        'title' => 'Novo juiz adicionado: {judge_name}',
                        'content' => 'Um novo juiz "{judge_name}" foi adicionado ao sistema. Tribunal: {court}. Especialização: {specialization}.'
                    ],
                    'pt-BR' => [
                        'title' => 'Novo juiz adicionado: {judge_name}',
                        'content' => 'Um novo juiz "{judge_name}" foi adicionado ao sistema. Tribunal: {court}. Especialização: {specialization}.'
                    ],
                    'ru' => [
                        'title' => 'Добавлен новый судья: {judge_name}',
                        'content' => 'Новый судья "{judge_name}" был добавлен в систему. Суд: {court}. Специализация: {specialization}.'
                    ],
                    'tr' => [
                        'title' => 'Yeni Hâkim Eklendi: {judge_name}',
                        'content' => 'Sisteme yeni bir hâkim "{judge_name}" eklendi. Mahkeme: {court}. Uzmanlık alanı: {specialization}.'
                    ],
                    'zh' => [
                        'title' => '已添加新法官: {judge_name}',
                        'content' => '新法官 "{judge_name}" 已添加到系统中。法院：{court}。专长：{specialization}。'
                    ],
                ]
            ],

            [
                'name' => 'New License',
                'type' => 'slack',
                'translations' => [
                    'en' => [
                        'title' => 'New Professional License: {license_number}',
                        'content' => 'A new professional license "{license_number}" has been added. Type: {license_type}. Issuing Authority: {issuing_authority}. Expiry: {expiry_date}.'
                    ],
                    'es' => [
                        'title' => 'Nueva licencia profesional: {license_number}',
                        'content' => 'Se ha agregado una nueva licencia profesional "{license_number}". Tipo: {license_type}. Autoridad emisora: {issuing_authority}. Vencimiento: {expiry_date}.'
                    ],
                    'ar' => [
                        'title' => 'تمت إضافة ترخيص مهني جديد: {license_number}',
                        'content' => 'تمت إضافة الترخيص المهني الجديد "{license_number}". النوع: {license_type}. جهة الإصدار: {issuing_authority}. تاريخ الانتهاء: {expiry_date}.'
                    ],
                    'da' => [
                        'title' => 'Ny professionel licens: {license_number}',
                        'content' => 'En ny professionel licens "{license_number}" er blevet tilføjet. Type: {license_type}. Udstedende myndighed: {issuing_authority}. Udløbsdato: {expiry_date}.'
                    ],
                    'de' => [
                        'title' => 'Neue Berufslizenz: {license_number}',
                        'content' => 'Eine neue Berufslizenz "{license_number}" wurde hinzugefügt. Typ: {license_type}. Ausstellende Behörde: {issuing_authority}. Ablaufdatum: {expiry_date}.'
                    ],
                    'fr' => [
                        'title' => 'Nouvelle licence professionnelle : {license_number}',
                        'content' => 'Une nouvelle licence professionnelle "{license_number}" a été ajoutée. Type : {license_type}. Autorité émettrice : {issuing_authority}. Expiration : {expiry_date}.'
                    ],
                    'he' => [
                        'title' => 'נוספה רישיון מקצועי חדש: {license_number}',
                        'content' => 'הרישיון המקצועי החדש "{license_number}" נוסף למערכת. סוג: {license_type}. רשות מנפיקה: {issuing_authority}. תוקף: {expiry_date}.'
                    ],
                    'it' => [
                        'title' => 'Nuova licenza professionale: {license_number}',
                        'content' => 'È stata aggiunta una nuova licenza professionale "{license_number}". Tipo: {license_type}. Autorità emittente: {issuing_authority}. Scadenza: {expiry_date}.'
                    ],
                    'ja' => [
                        'title' => '新しい職業ライセンスが追加されました: {license_number}',
                        'content' => '新しい職業ライセンス「{license_number}」が追加されました。種類: {license_type}。発行機関: {issuing_authority}。有効期限: {expiry_date}。'
                    ],
                    'nl' => [
                        'title' => 'Nieuwe professionele licentie: {license_number}',
                        'content' => 'Een nieuwe professionele licentie "{license_number}" is toegevoegd. Type: {license_type}. Uitgevende autoriteit: {issuing_authority}. Vervaldatum: {expiry_date}.'
                    ],
                    'pl' => [
                        'title' => 'Dodano nową licencję zawodową: {license_number}',
                        'content' => 'Nowa licencja zawodowa "{license_number}" została dodana. Typ: {license_type}. Organ wydający: {issuing_authority}. Data wygaśnięcia: {expiry_date}.'
                    ],
                    'pt' => [
                        'title' => 'Nova licença profissional: {license_number}',
                        'content' => 'Uma nova licença profissional "{license_number}" foi adicionada. Tipo: {license_type}. Autoridade emissora: {issuing_authority}. Validade: {expiry_date}.'
                    ],
                    'pt-BR' => [
                        'title' => 'Nova licença profissional: {license_number}',
                        'content' => 'Uma nova licença profissional "{license_number}" foi adicionada. Tipo: {license_type}. Autoridade emissora: {issuing_authority}. Validade: {expiry_date}.'
                    ],
                    'ru' => [
                        'title' => 'Добавлена новая профессиональная лицензия: {license_number}',
                        'content' => 'Добавлена новая профессиональная лицензия "{license_number}". Тип: {license_type}. Выдавший орган: {issuing_authority}. Срок действия: {expiry_date}.'
                    ],
                    'tr' => [
                        'title' => 'Yeni Profesyonel Lisans: {license_number}',
                        'content' => 'Yeni bir profesyonel lisans "{license_number}" eklendi. Tür: {license_type}. Veren Otorite: {issuing_authority}. Bitiş Tarihi: {expiry_date}.'
                    ],
                    'zh' => [
                        'title' => '已添加新职业许可证: {license_number}',
                        'content' => '新的职业许可证 "{license_number}" 已添加。类型：{license_type}。签发机构：{issuing_authority}。到期日：{expiry_date}。'
                    ],
                ]
            ],

            [
                'name' => 'New Regulatory Body',
                'type' => 'slack',
                'translations' => [
                    'en' => [
                        'title' => 'New Regulatory Body: {body_name}',
                        'content' => 'A new regulatory body "{body_name}" has been added. Jurisdiction: {jurisdiction}. Contact: {contact_info}.'
                    ],
                    'es' => [
                        'title' => 'Nuevo organismo regulador: {body_name}',
                        'content' => 'Se ha agregado un nuevo organismo regulador "{body_name}". Jurisdicción: {jurisdiction}. Contacto: {contact_info}.'
                    ],
                    'ar' => [
                        'title' => 'تمت إضافة جهة تنظيمية جديدة: {body_name}',
                        'content' => 'تمت إضافة الجهة التنظيمية الجديدة "{body_name}". الولاية القضائية: {jurisdiction}. جهة الاتصال: {contact_info}.'
                    ],
                    'da' => [
                        'title' => 'Ny tilsynsmyndighed tilføjet: {body_name}',
                        'content' => 'En ny tilsynsmyndighed "{body_name}" er blevet tilføjet. Jurisdiktion: {jurisdiction}. Kontakt: {contact_info}.'
                    ],
                    'de' => [
                        'title' => 'Neue Aufsichtsbehörde hinzugefügt: {body_name}',
                        'content' => 'Eine neue Aufsichtsbehörde "{body_name}" wurde hinzugefügt. Zuständigkeit: {jurisdiction}. Kontakt: {contact_info}.'
                    ],
                    'fr' => [
                        'title' => 'Nouvel organisme de régulation : {body_name}',
                        'content' => 'Un nouvel organisme de régulation "{body_name}" a été ajouté. Juridiction : {jurisdiction}. Contact : {contact_info}.'
                    ],
                    'he' => [
                        'title' => 'נוסף גוף רגולטורי חדש: {body_name}',
                        'content' => 'הגוף הרגולטורי החדש "{body_name}" נוסף למערכת. סמכות שיפוט: {jurisdiction}. איש קשר: {contact_info}.'
                    ],
                    'it' => [
                        'title' => 'Nuovo ente regolatore: {body_name}',
                        'content' => 'È stato aggiunto un nuovo ente regolatore "{body_name}". Giurisdizione: {jurisdiction}. Contatto: {contact_info}.'
                    ],
                    'ja' => [
                        'title' => '新しい規制機関が追加されました: {body_name}',
                        'content' => '新しい規制機関「{body_name}」が追加されました。管轄: {jurisdiction}。連絡先: {contact_info}。'
                    ],
                    'nl' => [
                        'title' => 'Nieuwe regelgevende instantie toegevoegd: {body_name}',
                        'content' => 'Een nieuwe regelgevende instantie "{body_name}" is toegevoegd. Rechtsgebied: {jurisdiction}. Contact: {contact_info}.'
                    ],
                    'pl' => [
                        'title' => 'Dodano nowy organ regulacyjny: {body_name}',
                        'content' => 'Nowy organ regulacyjny "{body_name}" został dodany. Jurysdykcja: {jurisdiction}. Kontakt: {contact_info}.'
                    ],
                    'pt' => [
                        'title' => 'Novo órgão regulador: {body_name}',
                        'content' => 'Um novo órgão regulador "{body_name}" foi adicionado. Jurisdição: {jurisdiction}. Contato: {contact_info}.'
                    ],
                    'pt-BR' => [
                        'title' => 'Novo órgão regulador: {body_name}',
                        'content' => 'Um novo órgão regulador "{body_name}" foi adicionado. Jurisdição: {jurisdiction}. Contato: {contact_info}.'
                    ],
                    'ru' => [
                        'title' => 'Добавлен новый регулирующий орган: {body_name}',
                        'content' => 'Новый регулирующий орган "{body_name}" был добавлен. Юрисдикция: {jurisdiction}. Контакт: {contact_info}.'
                    ],
                    'tr' => [
                        'title' => 'Yeni Düzenleyici Kurum: {body_name}',
                        'content' => 'Yeni bir düzenleyici kurum "{body_name}" eklendi. Yargı Yetkisi: {jurisdiction}. İletişim: {contact_info}.'
                    ],
                    'zh' => [
                        'title' => '已添加新监管机构: {body_name}',
                        'content' => '新的监管机构 "{body_name}" 已添加。管辖权：{jurisdiction}。联系方式：{contact_info}。'
                    ],
                ]
            ],

            [
                'name' => 'New CLE Record',
                'type' => 'slack',
                'translations' => [
                    'en' => [
                        'title' => 'New CLE Record: {course_title}',
                        'content' => 'A new CLE record "{course_title}" has been added. Credits: {credits_earned}. Completion Date: {completion_date}. Provider: {provider}.'
                    ],
                    'es' => [
                        'title' => 'Nuevo registro CLE: {course_title}',
                        'content' => 'Se ha agregado un nuevo registro CLE "{course_title}". Créditos: {credits_earned}. Fecha de finalización: {completion_date}. Proveedor: {provider}.'
                    ],
                    'ar' => [
                        'title' => 'تمت إضافة سجل CLE جديد: {course_title}',
                        'content' => 'تمت إضافة سجل CLE الجديد "{course_title}". الاعتمادات: {credits_earned}. تاريخ الإكمال: {completion_date}. المزود: {provider}.'
                    ],
                    'da' => [
                        'title' => 'Ny CLE-post tilføjet: {course_title}',
                        'content' => 'En ny CLE-post "{course_title}" er blevet tilføjet. Point: {credits_earned}. Afslutningsdato: {completion_date}. Udbyder: {provider}.'
                    ],
                    'de' => [
                        'title' => 'Neuer CLE-Eintrag: {course_title}',
                        'content' => 'Ein neuer CLE-Eintrag "{course_title}" wurde hinzugefügt. Punkte: {credits_earned}. Abschlussdatum: {completion_date}. Anbieter: {provider}.'
                    ],
                    'fr' => [
                        'title' => 'Nouvel enregistrement CLE : {course_title}',
                        'content' => 'Un nouvel enregistrement CLE "{course_title}" a été ajouté. Crédits : {credits_earned}. Date d\'achèvement : {completion_date}. Fournisseur : {provider}.'
                    ],
                    'he' => [
                        'title' => 'נוסף רישום CLE חדש: {course_title}',
                        'content' => 'נוסף רישום CLE חדש "{course_title}". נקודות זכות: {credits_earned}. תאריך השלמה: {completion_date}. ספק: {provider}.'
                    ],
                    'it' => [
                        'title' => 'Nuovo record CLE: {course_title}',
                        'content' => 'È stato aggiunto un nuovo record CLE "{course_title}". Crediti: {credits_earned}. Data di completamento: {completion_date}. Fornitore: {provider}.'
                    ],
                    'ja' => [
                        'title' => '新しいCLE記録が追加されました: {course_title}',
                        'content' => '新しいCLE記録「{course_title}」が追加されました。単位: {credits_earned}。修了日: {completion_date}。提供者: {provider}。'
                    ],
                    'nl' => [
                        'title' => 'Nieuw CLE-record toegevoegd: {course_title}',
                        'content' => 'Een nieuw CLE-record "{course_title}" is toegevoegd. Punten: {credits_earned}. Voltooiingsdatum: {completion_date}. Aanbieder: {provider}.'
                    ],
                    'pl' => [
                        'title' => 'Dodano nowy wpis CLE: {course_title}',
                        'content' => 'Nowy wpis CLE "{course_title}" został dodany. Punkty: {credits_earned}. Data ukończenia: {completion_date}. Dostawca: {provider}.'
                    ],
                    'pt' => [
                        'title' => 'Novo registro CLE: {course_title}',
                        'content' => 'Um novo registro CLE "{course_title}" foi adicionado. Créditos: {credits_earned}. Data de conclusão: {completion_date}. Provedor: {provider}.'
                    ],
                    'pt-BR' => [
                        'title' => 'Novo registro CLE: {course_title}',
                        'content' => 'Um novo registro CLE "{course_title}" foi adicionado. Créditos: {credits_earned}. Data de conclusão: {completion_date}. Provedor: {provider}.'
                    ],
                    'ru' => [
                        'title' => 'Новая запись CLE: {course_title}',
                        'content' => 'Добавлена новая запись CLE "{course_title}". Кредиты: {credits_earned}. Дата завершения: {completion_date}. Провайдер: {provider}.'
                    ],
                    'tr' => [
                        'title' => 'Yeni CLE Kaydı: {course_title}',
                        'content' => 'Yeni bir CLE kaydı "{course_title}" eklendi. Krediler: {credits_earned}. Tamamlama Tarihi: {completion_date}. Sağlayıcı: {provider}.'
                    ],
                    'zh' => [
                        'title' => '已添加新的CLE记录: {course_title}',
                        'content' => '新的CLE记录 "{course_title}" 已添加。学分：{credits_earned}。完成日期：{completion_date}。提供者：{provider}。'
                    ],
                ]
            ],
            [
                'name' => 'Team Member Created',
                'type' => 'slack',
                'translations' => [
                    'en' => [
                        'title' => 'New Team Member: {member_name}',
                        'content' => 'A new team member "{member_name}" has been added to the system. Email: {email}. Role: {role}.'
                    ],
                    'es' => [
                        'title' => 'Nuevo miembro del equipo: {member_name}',
                        'content' => 'Se ha agregado un nuevo miembro del equipo "{member_name}" al sistema. Correo electrónico: {email}. Rol: {role}.'
                    ],
                    'ar' => [
                        'title' => 'عضو فريق جديد: {member_name}',
                        'content' => 'تمت إضافة عضو فريق جديد "{member_name}" إلى النظام. البريد الإلكتروني: {email}. الدور: {role}.'
                    ],
                    'da' => [
                        'title' => 'Nyt teammedlem: {member_name}',
                        'content' => 'Et nyt teammedlem "{member_name}" er blevet tilføjet til systemet. E-mail: {email}. Rolle: {role}.'
                    ],
                    'de' => [
                        'title' => 'Neues Teammitglied: {member_name}',
                        'content' => 'Ein neues Teammitglied "{member_name}" wurde dem System hinzugefügt. E-Mail: {email}. Rolle: {role}.'
                    ],
                    'fr' => [
                        'title' => 'Nouveau membre de l\'équipe : {member_name}',
                        'content' => 'Un nouveau membre de l\'équipe "{member_name}" a été ajouté au système. E-mail : {email}. Rôle : {role}.'
                    ],
                    'he' => [
                        'title' => 'נוסף חבר צוות חדש: {member_name}',
                        'content' => 'נוסף חבר צוות חדש "{member_name}" למערכת. אימייל: {email}. תפקיד: {role}.'
                    ],
                    'it' => [
                        'title' => 'Nuovo membro del team: {member_name}',
                        'content' => 'È stato aggiunto un nuovo membro del team "{member_name}" al sistema. Email: {email}. Ruolo: {role}.'
                    ],
                    'ja' => [
                        'title' => '新しいチームメンバー: {member_name}',
                        'content' => '新しいチームメンバー「{member_name}」がシステムに追加されました。メール: {email}。役割: {role}。'
                    ],
                    'nl' => [
                        'title' => 'Nieuw teamlid: {member_name}',
                        'content' => 'Een nieuw teamlid "{member_name}" is toegevoegd aan het systeem. E-mail: {email}. Rol: {role}.'
                    ],
                    'pl' => [
                        'title' => 'Nowy członek zespołu: {member_name}',
                        'content' => 'Nowy członek zespołu "{member_name}" został dodany do systemu. E-mail: {email}. Rola: {role}.'
                    ],
                    'pt' => [
                        'title' => 'Novo membro da equipe: {member_name}',
                        'content' => 'Um novo membro da equipe "{member_name}" foi adicionado ao sistema. E-mail: {email}. Função: {role}.'
                    ],
                    'pt-BR' => [
                        'title' => 'Novo membro da equipe: {member_name}',
                        'content' => 'Um novo membro da equipe "{member_name}" foi adicionado ao sistema. E-mail: {email}. Função: {role}.'
                    ],
                    'ru' => [
                        'title' => 'Новый участник команды: {member_name}',
                        'content' => 'В систему добавлен новый участник команды "{member_name}". Эл. почта: {email}. Роль: {role}.'
                    ],
                    'tr' => [
                        'title' => 'Yeni Ekip Üyesi: {member_name}',
                        'content' => 'Yeni bir ekip üyesi "{member_name}" sisteme eklendi. E-posta: {email}. Rol: {role}.'
                    ],
                    'zh' => [
                        'title' => '新团队成员: {member_name}',
                        'content' => '新的团队成员“{member_name}”已添加到系统。电子邮件：{email}。角色：{role}。'
                    ],
                ]
            ],
            [
                'name' => 'New Case',
                'type' => 'twilio',
                'translations' => [
                    'en' => [
                        'title' => 'New Case: {case_number}',
                        'content' => 'A new case ({case_number}) has been opened for you. Type: {case_type}. Assigned by {created_by}.'
                    ],
                    'es' => [
                        'title' => 'Nuevo caso: {case_number}',
                        'content' => 'Se ha abierto un nuevo caso ({case_number}) para usted. Tipo: {case_type}. Asignado por {created_by}.'
                    ],
                    'ar' => [
                        'title' => 'قضية جديدة: {case_number}',
                        'content' => 'تم فتح قضية جديدة ({case_number}) لك. النوع: {case_type}. تم التعيين بواسطة {created_by}.'
                    ],
                    'da' => [
                        'title' => 'Ny sag: {case_number}',
                        'content' => 'En ny sag ({case_number}) er blevet oprettet til dig. Type: {case_type}. Tildelt af {created_by}.'
                    ],
                    'de' => [
                        'title' => 'Neuer Fall: {case_number}',
                        'content' => 'Ein neuer Fall ({case_number}) wurde für Sie eröffnet. Typ: {case_type}. Zugewiesen von {created_by}.'
                    ],
                    'fr' => [
                        'title' => 'Nouveau dossier : {case_number}',
                        'content' => 'Un nouveau dossier ({case_number}) a été ouvert pour vous. Type : {case_type}. Assigné par {created_by}.'
                    ],
                    'he' => [
                        'title' => 'תיק חדש: {case_number}',
                        'content' => 'נפתח עבורך תיק חדש ({case_number}). סוג: {case_type}. הוקצה על ידי {created_by}.'
                    ],
                    'it' => [
                        'title' => 'Nuovo caso: {case_number}',
                        'content' => 'È stato aperto un nuovo caso ({case_number}) per te. Tipo: {case_type}. Assegnato da {created_by}.'
                    ],
                    'ja' => [
                        'title' => '新しい案件: {case_number}',
                        'content' => 'あなたのために新しい案件 ({case_number}) が作成されました。種類: {case_type}。担当者: {created_by}。'
                    ],
                    'nl' => [
                        'title' => 'Nieuwe zaak: {case_number}',
                        'content' => 'Een nieuwe zaak ({case_number}) is voor u geopend. Type: {case_type}. Toegevoegd door {created_by}.'
                    ],
                    'pl' => [
                        'title' => 'Nowa sprawa: {case_number}',
                        'content' => 'Nowa sprawa ({case_number}) została otwarta dla Ciebie. Typ: {case_type}. Przydzielony przez {created_by}.'
                    ],
                    'pt' => [
                        'title' => 'Novo caso: {case_number}',
                        'content' => 'Um novo caso ({case_number}) foi aberto para você. Tipo: {case_type}. Atribuído por {created_by}.'
                    ],
                    'pt-BR' => [
                        'title' => 'Novo caso: {case_number}',
                        'content' => 'Um novo caso ({case_number}) foi aberto para você. Tipo: {case_type}. Atribuído por {created_by}.'
                    ],
                    'ru' => [
                        'title' => 'Новое дело: {case_number}',
                        'content' => 'Для вас открыто новое дело ({case_number}). Тип: {case_type}. Назначено {created_by}.'
                    ],
                    'tr' => [
                        'title' => 'Yeni Dava: {case_number}',
                        'content' => 'Sizin için yeni bir dava ({case_number}) açıldı. Tür: {case_type}. Atayan: {created_by}.'
                    ],
                    'zh' => [
                        'title' => '新案件: {case_number}',
                        'content' => '为您开立了新案件 ({case_number})。类型：{case_type}。分配人：{created_by}。'
                    ],
                ]
            ],

            [
                'name' => 'New Client',
                'type' => 'twilio',
                'translations' => [
                    'en' => [
                        'title' => 'New Client: {client_name}',
                        'content' => 'Welcome {client_name}! Your profile has been added successfully. Type: {client_type}.'
                    ],
                    'es' => [
                        'title' => 'Nuevo cliente: {client_name}',
                        'content' => '¡Bienvenido {client_name}! Su perfil se ha agregado correctamente. Tipo: {client_type}.'
                    ],
                    'ar' => [
                        'title' => 'عميل جديد: {client_name}',
                        'content' => 'مرحبًا {client_name}! تم إضافة ملفك الشخصي بنجاح. النوع: {client_type}.'
                    ],
                    'da' => [
                        'title' => 'Ny klient: {client_name}',
                        'content' => 'Velkommen {client_name}! Din profil er blevet tilføjet med succes. Type: {client_type}.'
                    ],
                    'de' => [
                        'title' => 'Neuer Kunde: {client_name}',
                        'content' => 'Willkommen {client_name}! Ihr Profil wurde erfolgreich hinzugefügt. Typ: {client_type}.'
                    ],
                    'fr' => [
                        'title' => 'Nouveau client : {client_name}',
                        'content' => 'Bienvenue {client_name} ! Votre profil a été ajouté avec succès. Type : {client_type}.'
                    ],
                    'he' => [
                        'title' => 'לקוח חדש: {client_name}',
                        'content' => 'ברוך הבא {client_name}! הפרופיל שלך נוסף בהצלחה. סוג: {client_type}.'
                    ],
                    'it' => [
                        'title' => 'Nuovo cliente: {client_name}',
                        'content' => 'Benvenuto {client_name}! Il tuo profilo è stato aggiunto con successo. Tipo: {client_type}.'
                    ],
                    'ja' => [
                        'title' => '新しいクライアント: {client_name}',
                        'content' => 'ようこそ {client_name} さん！あなたのプロフィールが正常に追加されました。タイプ: {client_type}。'
                    ],
                    'nl' => [
                        'title' => 'Nieuwe klant: {client_name}',
                        'content' => 'Welkom {client_name}! Uw profiel is succesvol toegevoegd. Type: {client_type}.'
                    ],
                    'pl' => [
                        'title' => 'Nowy klient: {client_name}',
                        'content' => 'Witamy {client_name}! Twój profil został pomyślnie dodany. Typ: {client_type}.'
                    ],
                    'pt' => [
                        'title' => 'Novo cliente: {client_name}',
                        'content' => 'Bem-vindo {client_name}! O seu perfil foi adicionado com sucesso. Tipo: {client_type}.'
                    ],
                    'pt-BR' => [
                        'title' => 'Novo cliente: {client_name}',
                        'content' => 'Bem-vindo {client_name}! Seu perfil foi adicionado com sucesso. Tipo: {client_type}.'
                    ],
                    'ru' => [
                        'title' => 'Новый клиент: {client_name}',
                        'content' => 'Добро пожаловать, {client_name}! Ваш профиль успешно добавлен. Тип: {client_type}.'
                    ],
                    'tr' => [
                        'title' => 'Yeni Müşteri: {client_name}',
                        'content' => 'Hoş geldiniz {client_name}! Profiliniz başarıyla eklendi. Tür: {client_type}.'
                    ],
                    'zh' => [
                        'title' => '新客户: {client_name}',
                        'content' => '欢迎 {client_name}！您的资料已成功添加。类型：{client_type}。'
                    ],
                ]
            ],

            [
                'name' => 'New Hearing',
                'type' => 'twilio',
                'translations' => [
                    'en' => [
                        'title' => 'New Hearing: {case_number}',
                        'content' => 'Your hearing for case {case_number} is scheduled on {hearing_date} at {court}. Judge: {judge}.'
                    ],
                    'es' => [
                        'title' => 'Nueva audiencia: {case_number}',
                        'content' => 'Su audiencia para el caso {case_number} está programada para el {hearing_date} en {court}. Juez: {judge}.'
                    ],
                    'ar' => [
                        'title' => 'جلسة جديدة: {case_number}',
                        'content' => 'تم تحديد جلستك للقضية {case_number} في {court} بتاريخ {hearing_date}. القاضي: {judge}.'
                    ],
                    'da' => [
                        'title' => 'Ny høring: {case_number}',
                        'content' => 'Din høring for sag {case_number} er planlagt til {hearing_date} ved {court}. Dommer: {judge}.'
                    ],
                    'de' => [
                        'title' => 'Neue Anhörung: {case_number}',
                        'content' => 'Ihre Anhörung für den Fall {case_number} ist für den {hearing_date} am {court} geplant. Richter: {judge}.'
                    ],
                    'fr' => [
                        'title' => 'Nouvelle audience : {case_number}',
                        'content' => 'Votre audience pour l\'affaire {case_number} est prévue le {hearing_date} au tribunal {court}. Juge : {judge}.'
                    ],
                    'he' => [
                        'title' => 'דיון חדש: {case_number}',
                        'content' => 'הדיון שלך בתיק {case_number} נקבע ליום {hearing_date} בבית המשפט {court}. שופט: {judge}.'
                    ],
                    'it' => [
                        'title' => 'Nuova udienza: {case_number}',
                        'content' => 'La tua udienza per il caso {case_number} è fissata per il {hearing_date} presso {court}. Giudice: {judge}.'
                    ],
                    'ja' => [
                        'title' => '新しい審理: {case_number}',
                        'content' => '{case_number} の審理は {hearing_date} に {court} で予定されています。裁判官: {judge}。'
                    ],
                    'nl' => [
                        'title' => 'Nieuwe zitting: {case_number}',
                        'content' => 'Uw zitting voor zaak {case_number} is gepland op {hearing_date} bij {court}. Rechter: {judge}.'
                    ],
                    'pl' => [
                        'title' => 'Nowe przesłuchanie: {case_number}',
                        'content' => 'Twoje przesłuchanie w sprawie {case_number} zaplanowano na {hearing_date} w {court}. Sędzia: {judge}.'
                    ],
                    'pt' => [
                        'title' => 'Nova audiência: {case_number}',
                        'content' => 'Sua audiência do caso {case_number} está marcada para {hearing_date} no tribunal {court}. Juiz: {judge}.'
                    ],
                    'pt-BR' => [
                        'title' => 'Nova audiência: {case_number}',
                        'content' => 'Sua audiência do caso {case_number} está marcada para {hearing_date} no tribunal {court}. Juiz: {judge}.'
                    ],
                    'ru' => [
                        'title' => 'Новое слушание: {case_number}',
                        'content' => 'Ваше слушание по делу {case_number} назначено на {hearing_date} в суде {court}. Судья: {judge}.'
                    ],
                    'tr' => [
                        'title' => 'Yeni Duruşma: {case_number}',
                        'content' => '{case_number} numaralı dava için duruşmanız {hearing_date} tarihinde {court}’ta planlandı. Hakim: {judge}.'
                    ],
                    'zh' => [
                        'title' => '新听证会: {case_number}',
                        'content' => '您的案件 {case_number} 的听证会已安排在 {hearing_date}，地点：{court}。法官：{judge}。'
                    ],
                ]
            ],

            [
                'name' => 'New Invoice',
                'type' => 'twilio',
                'translations' => [
                    'en' => [
                        'title' => 'New Invoice: {invoice_number}',
                        'content' => 'Invoice {invoice_number} issued for {client_name}. Amount: {amount}. Due on {due_date}.'
                    ],
                    'es' => [
                        'title' => 'Nueva factura: {invoice_number}',
                        'content' => 'Se emitió la factura {invoice_number} para {client_name}. Importe: {amount}. Vence el {due_date}.'
                    ],
                    'ar' => [
                        'title' => 'فاتورة جديدة: {invoice_number}',
                        'content' => 'تم إصدار الفاتورة {invoice_number} للعميل {client_name}. المبلغ: {amount}. تاريخ الاستحقاق: {due_date}.'
                    ],
                    'da' => [
                        'title' => 'Ny faktura: {invoice_number}',
                        'content' => 'Faktura {invoice_number} er udstedt til {client_name}. Beløb: {amount}. Forfalder den {due_date}.'
                    ],
                    'de' => [
                        'title' => 'Neue Rechnung: {invoice_number}',
                        'content' => 'Rechnung {invoice_number} wurde für {client_name} ausgestellt. Betrag: {amount}. Fällig am {due_date}.'
                    ],
                    'fr' => [
                        'title' => 'Nouvelle facture : {invoice_number}',
                        'content' => 'La facture {invoice_number} a été émise pour {client_name}. Montant : {amount}. Échéance : {due_date}.'
                    ],
                    'he' => [
                        'title' => 'חשבונית חדשה: {invoice_number}',
                        'content' => 'הונפקה חשבונית {invoice_number} עבור {client_name}. סכום: {amount}. תאריך יעד: {due_date}.'
                    ],
                    'it' => [
                        'title' => 'Nuova fattura: {invoice_number}',
                        'content' => 'La fattura {invoice_number} è stata emessa per {client_name}. Importo: {amount}. Scadenza: {due_date}.'
                    ],
                    'ja' => [
                        'title' => '新しい請求書: {invoice_number}',
                        'content' => '{client_name} 向けに請求書 {invoice_number} が発行されました。金額: {amount}。期限: {due_date}。'
                    ],
                    'nl' => [
                        'title' => 'Nieuwe factuur: {invoice_number}',
                        'content' => 'Factuur {invoice_number} is uitgegeven voor {client_name}. Bedrag: {amount}. Vervaldatum: {due_date}.'
                    ],
                    'pl' => [
                        'title' => 'Nowa faktura: {invoice_number}',
                        'content' => 'Wystawiono fakturę {invoice_number} dla {client_name}. Kwota: {amount}. Termin płatności: {due_date}.'
                    ],
                    'pt' => [
                        'title' => 'Nova fatura: {invoice_number}',
                        'content' => 'A fatura {invoice_number} foi emitida para {client_name}. Valor: {amount}. Vencimento: {due_date}.'
                    ],
                    'pt-BR' => [
                        'title' => 'Nova fatura: {invoice_number}',
                        'content' => 'A fatura {invoice_number} foi emitida para {client_name}. Valor: {amount}. Vencimento: {due_date}.'
                    ],
                    'ru' => [
                        'title' => 'Новый счет: {invoice_number}',
                        'content' => 'Счет {invoice_number} выставлен для {client_name}. Сумма: {amount}. Срок оплаты: {due_date}.'
                    ],
                    'tr' => [
                        'title' => 'Yeni Fatura: {invoice_number}',
                        'content' => '{client_name} için {invoice_number} numaralı fatura düzenlendi. Tutar: {amount}. Son tarih: {due_date}.'
                    ],
                    'zh' => [
                        'title' => '新发票: {invoice_number}',
                        'content' => '已为 {client_name} 开具发票 {invoice_number}。金额: {amount}。到期日: {due_date}。'
                    ],
                ]
            ],

            [
                'name' => 'Invoice Sent',
                'type' => 'twilio',
                'translations' => [
                    'en' => [
                        'title' => 'Invoice Sent: {invoice_number}',
                        'content' => 'Your invoice {invoice_number} has been sent. Please review and complete payment by {due_date}.'
                    ],
                    'es' => [
                        'title' => 'Factura enviada: {invoice_number}',
                        'content' => 'Su factura {invoice_number} ha sido enviada. Revísela y complete el pago antes del {due_date}.'
                    ],
                    'ar' => [
                        'title' => 'تم إرسال الفاتورة: {invoice_number}',
                        'content' => 'تم إرسال فاتورتك {invoice_number}. يرجى المراجعة وإكمال الدفع قبل {due_date}.'
                    ],
                    'da' => [
                        'title' => 'Faktura sendt: {invoice_number}',
                        'content' => 'Din faktura {invoice_number} er blevet sendt. Gennemgå og betal inden {due_date}.'
                    ],
                    'de' => [
                        'title' => 'Rechnung gesendet: {invoice_number}',
                        'content' => 'Ihre Rechnung {invoice_number} wurde gesendet. Bitte prüfen und bezahlen bis {due_date}.'
                    ],
                    'fr' => [
                        'title' => 'Facture envoyée : {invoice_number}',
                        'content' => 'Votre facture {invoice_number} a été envoyée. Veuillez vérifier et effectuer le paiement avant le {due_date}.'
                    ],
                    'he' => [
                        'title' => 'חשבונית נשלחה: {invoice_number}',
                        'content' => 'החשבונית שלך {invoice_number} נשלחה. אנא בדוק ושלם עד {due_date}.'
                    ],
                    'it' => [
                        'title' => 'Fattura inviata: {invoice_number}',
                        'content' => 'La tua fattura {invoice_number} è stata inviata. Controlla e completa il pagamento entro il {due_date}.'
                    ],
                    'ja' => [
                        'title' => '請求書送付済み: {invoice_number}',
                        'content' => '請求書 {invoice_number} が送信されました。{due_date} までに確認して支払いを完了してください。'
                    ],
                    'nl' => [
                        'title' => 'Factuur verzonden: {invoice_number}',
                        'content' => 'Uw factuur {invoice_number} is verzonden. Controleer en betaal vóór {due_date}.'
                    ],
                    'pl' => [
                        'title' => 'Faktura wysłana: {invoice_number}',
                        'content' => 'Twoja faktura {invoice_number} została wysłana. Sprawdź i dokonaj płatności do {due_date}.'
                    ],
                    'pt' => [
                        'title' => 'Fatura enviada: {invoice_number}',
                        'content' => 'A sua fatura {invoice_number} foi enviada. Verifique e conclua o pagamento até {due_date}.'
                    ],
                    'pt-BR' => [
                        'title' => 'Fatura enviada: {invoice_number}',
                        'content' => 'Sua fatura {invoice_number} foi enviada. Verifique e conclua o pagamento até {due_date}.'
                    ],
                    'ru' => [
                        'title' => 'Счет отправлен: {invoice_number}',
                        'content' => 'Ваш счет {invoice_number} был отправлен. Проверьте и оплатите до {due_date}.'
                    ],
                    'tr' => [
                        'title' => 'Fatura Gönderildi: {invoice_number}',
                        'content' => 'Faturanız {invoice_number} gönderildi. Lütfen {due_date} tarihine kadar inceleyip ödemeyi tamamlayın.'
                    ],
                    'zh' => [
                        'title' => '发票已发送: {invoice_number}',
                        'content' => '您的发票 {invoice_number} 已发送。请在 {due_date} 前查看并完成付款。'
                    ],
                ]
            ],

            [
                'name' => 'New Court',
                'type' => 'twilio',
                'translations' => [
                    'en' => [
                        'title' => 'New Court: {court_name}',
                        'content' => 'A new court "{court_name}" has been added to our records. Location: {location}.'
                    ],
                    'es' => [
                        'title' => 'Nuevo tribunal: {court_name}',
                        'content' => 'Se ha agregado un nuevo tribunal "{court_name}" a nuestros registros. Ubicación: {location}.'
                    ],
                    'ar' => [
                        'title' => 'محكمة جديدة: {court_name}',
                        'content' => 'تمت إضافة محكمة جديدة "{court_name}" إلى سجلاتنا. الموقع: {location}.'
                    ],
                    'da' => [
                        'title' => 'Ny domstol: {court_name}',
                        'content' => 'En ny domstol "{court_name}" er blevet tilføjet til vores registre. Placering: {location}.'
                    ],
                    'de' => [
                        'title' => 'Neues Gericht: {court_name}',
                        'content' => 'Ein neues Gericht "{court_name}" wurde zu unseren Aufzeichnungen hinzugefügt. Standort: {location}.'
                    ],
                    'fr' => [
                        'title' => 'Nouveau tribunal : {court_name}',
                        'content' => 'Un nouveau tribunal "{court_name}" a été ajouté à nos registres. Emplacement : {location}.'
                    ],
                    'he' => [
                        'title' => 'בית משפט חדש: {court_name}',
                        'content' => 'בית משפט חדש "{court_name}" נוסף לרישומים שלנו. מיקום: {location}.'
                    ],
                    'it' => [
                        'title' => 'Nuovo tribunale: {court_name}',
                        'content' => 'È stato aggiunto un nuovo tribunale "{court_name}" ai nostri archivi. Posizione: {location}.'
                    ],
                    'ja' => [
                        'title' => '新しい裁判所: {court_name}',
                        'content' => '新しい裁判所「{court_name}」が記録に追加されました。場所: {location}。'
                    ],
                    'nl' => [
                        'title' => 'Nieuwe rechtbank: {court_name}',
                        'content' => 'Een nieuwe rechtbank "{court_name}" is toegevoegd aan onze administratie. Locatie: {location}.'
                    ],
                    'pl' => [
                        'title' => 'Nowy sąd: {court_name}',
                        'content' => 'Nowy sąd "{court_name}" został dodany do naszych rejestrów. Lokalizacja: {location}.'
                    ],
                    'pt' => [
                        'title' => 'Novo tribunal: {court_name}',
                        'content' => 'Um novo tribunal "{court_name}" foi adicionado aos nossos registros. Localização: {location}.'
                    ],
                    'pt-BR' => [
                        'title' => 'Novo tribunal: {court_name}',
                        'content' => 'Um novo tribunal "{court_name}" foi adicionado aos nossos registros. Localização: {location}.'
                    ],
                    'ru' => [
                        'title' => 'Новый суд: {court_name}',
                        'content' => 'Новый суд "{court_name}" был добавлен в наши записи. Местоположение: {location}.'
                    ],
                    'tr' => [
                        'title' => 'Yeni Mahkeme: {court_name}',
                        'content' => 'Kayıtlarımıza yeni bir mahkeme "{court_name}" eklendi. Konum: {location}.'
                    ],
                    'zh' => [
                        'title' => '新法院: {court_name}',
                        'content' => '新的法院“{court_name}”已添加到我们的记录中。位置：{location}。'
                    ],
                ]
            ],

            [
                'name' => 'New Judge',
                'type' => 'twilio',
                'translations' => [
                    'en' => [
                        'title' => 'New Judge: {judge_name}',
                        'content' => 'Judge {judge_name} ({specialization}) has been assigned to court {court}.'
                    ],
                    'es' => [
                        'title' => 'Nuevo juez: {judge_name}',
                        'content' => 'El juez {judge_name} ({specialization}) ha sido asignado al tribunal {court}.'
                    ],
                    'ar' => [
                        'title' => 'قاضٍ جديد: {judge_name}',
                        'content' => 'تم تعيين القاضي {judge_name} ({specialization}) في المحكمة {court}.'
                    ],
                    'da' => [
                        'title' => 'Ny dommer: {judge_name}',
                        'content' => 'Dommer {judge_name} ({specialization}) er blevet tildelt retten {court}.'
                    ],
                    'de' => [
                        'title' => 'Neuer Richter: {judge_name}',
                        'content' => 'Richter {judge_name} ({specialization}) wurde dem Gericht {court} zugewiesen.'
                    ],
                    'fr' => [
                        'title' => 'Nouveau juge : {judge_name}',
                        'content' => 'Le juge {judge_name} ({specialization}) a été affecté au tribunal {court}.'
                    ],
                    'he' => [
                        'title' => 'שופט חדש: {judge_name}',
                        'content' => 'השופט {judge_name} ({specialization}) הוקצה לבית המשפט {court}.'
                    ],
                    'it' => [
                        'title' => 'Nuovo giudice: {judge_name}',
                        'content' => 'Il giudice {judge_name} ({specialization}) è stato assegnato al tribunale {court}.'
                    ],
                    'ja' => [
                        'title' => '新しい裁判官: {judge_name}',
                        'content' => '裁判官 {judge_name} ({specialization}) が裁判所 {court} に配属されました。'
                    ],
                    'nl' => [
                        'title' => 'Nieuwe rechter: {judge_name}',
                        'content' => 'Rechter {judge_name} ({specialization}) is toegewezen aan rechtbank {court}.'
                    ],
                    'pl' => [
                        'title' => 'Nowy sędzia: {judge_name}',
                        'content' => 'Sędzia {judge_name} ({specialization}) został przydzielony do sądu {court}.'
                    ],
                    'pt' => [
                        'title' => 'Novo juiz: {judge_name}',
                        'content' => 'O juiz {judge_name} ({specialization}) foi designado para o tribunal {court}.'
                    ],
                    'pt-BR' => [
                        'title' => 'Novo juiz: {judge_name}',
                        'content' => 'O juiz {judge_name} ({specialization}) foi designado para o tribunal {court}.'
                    ],
                    'ru' => [
                        'title' => 'Новый судья: {judge_name}',
                        'content' => 'Судья {judge_name} ({specialization}) назначен в суд {court}.'
                    ],
                    'tr' => [
                        'title' => 'Yeni Hakim: {judge_name}',
                        'content' => 'Hakim {judge_name} ({specialization}), {court} mahkemesine atandı.'
                    ],
                    'zh' => [
                        'title' => '新法官: {judge_name}',
                        'content' => '法官“{judge_name}”（{specialization}）已被分配到法院 {court}。'
                    ],
                ]
            ],

            [
                'name' => 'New Regulatory Body',
                'type' => 'twilio',
                'translations' => [
                    'en' => [
                        'title' => 'New Regulatory Body: {body_name}',
                        'content' => 'Regulatory body "{body_name}" has been added under {jurisdiction}. Contact: {contact_info}.'
                    ],
                    'es' => [
                        'title' => 'Nuevo organismo regulador: {body_name}',
                        'content' => 'El organismo regulador "{body_name}" se ha añadido bajo {jurisdiction}. Contacto: {contact_info}.'
                    ],
                    'ar' => [
                        'title' => 'هيئة تنظيمية جديدة: {body_name}',
                        'content' => 'تمت إضافة الهيئة التنظيمية "{body_name}" ضمن {jurisdiction}. جهة الاتصال: {contact_info}.'
                    ],
                    'da' => [
                        'title' => 'Ny tilsynsmyndighed: {body_name}',
                        'content' => 'Tilsynsmyndigheden "{body_name}" er blevet tilføjet under {jurisdiction}. Kontakt: {contact_info}.'
                    ],
                    'de' => [
                        'title' => 'Neue Aufsichtsbehörde: {body_name}',
                        'content' => 'Die Aufsichtsbehörde "{body_name}" wurde unter {jurisdiction} hinzugefügt. Kontakt: {contact_info}.'
                    ],
                    'fr' => [
                        'title' => 'Nouvel organisme de régulation : {body_name}',
                        'content' => 'L\'organisme de régulation "{body_name}" a été ajouté sous {jurisdiction}. Contact : {contact_info}.'
                    ],
                    'he' => [
                        'title' => 'גוף רגולטורי חדש: {body_name}',
                        'content' => 'גוף רגולטורי "{body_name}" נוסף תחת {jurisdiction}. איש קשר: {contact_info}.'
                    ],
                    'it' => [
                        'title' => 'Nuovo ente regolatore: {body_name}',
                        'content' => 'L\'ente regolatore "{body_name}" è stato aggiunto sotto {jurisdiction}. Contatto: {contact_info}.'
                    ],
                    'ja' => [
                        'title' => '新しい規制機関: {body_name}',
                        'content' => '規制機関「{body_name}」が{jurisdiction}の下に追加されました。連絡先: {contact_info}。'
                    ],
                    'nl' => [
                        'title' => 'Nieuwe regelgevende instantie: {body_name}',
                        'content' => 'De regelgevende instantie "{body_name}" is toegevoegd onder {jurisdiction}. Contact: {contact_info}.'
                    ],
                    'pl' => [
                        'title' => 'Nowy organ regulacyjny: {body_name}',
                        'content' => 'Organ regulacyjny "{body_name}" został dodany pod {jurisdiction}. Kontakt: {contact_info}.'
                    ],
                    'pt' => [
                        'title' => 'Novo órgão regulador: {body_name}',
                        'content' => 'O órgão regulador "{body_name}" foi adicionado sob {jurisdiction}. Contato: {contact_info}.'
                    ],
                    'pt-BR' => [
                        'title' => 'Novo órgão regulador: {body_name}',
                        'content' => 'O órgão regulador "{body_name}" foi adicionado sob {jurisdiction}. Contato: {contact_info}.'
                    ],
                    'ru' => [
                        'title' => 'Новый регулирующий орган: {body_name}',
                        'content' => 'Регулирующий орган "{body_name}" добавлен в {jurisdiction}. Контакт: {contact_info}.'
                    ],
                    'tr' => [
                        'title' => 'Yeni Düzenleyici Kurum: {body_name}',
                        'content' => 'Düzenleyici kurum "{body_name}" {jurisdiction} yetkisi altına eklendi. İletişim: {contact_info}.'
                    ],
                    'zh' => [
                        'title' => '新监管机构: {body_name}',
                        'content' => '监管机构“{body_name}”已添加至 {jurisdiction}。联系方式：{contact_info}。'
                    ],
                ]
            ],



        ];

        $companies = \App\Models\User::where('type', 'company')->get();

        foreach ($templates as $templateData) {
            // FIXED: Check both name AND type to prevent duplicates
            $template = NotificationTemplate::updateOrCreate(
                [
                    'name' => $templateData['name'],
                    'type' => $templateData['type']
                ],
                [
                    'name' => $templateData['name'],
                    'type' => $templateData['type']
                ]
            );

            // Create content for each company
            foreach ($companies as $company) {
                foreach ($langCodes as $langCode) {
                    $existingContent = NotificationTemplateLang::where('parent_id', $template->id)
                        ->where('lang', $langCode)
                        ->where('created_by', $company->id)
                        ->first();

                    if ($existingContent) {
                        continue;
                    }

                    $translation = $templateData['translations'][$langCode] ?? $templateData['translations']['en'];

                    NotificationTemplateLang::updateOrCreate([
                        'parent_id' => $template->id,
                        'lang' => $langCode,
                        'created_by' => $company->id
                    ], [
                        'title' => $translation['title'],
                        'content' => $translation['content']
                    ]);
                }
            }

            // Create content for global template
            foreach ($langCodes as $langCode) {
                $existingContent = NotificationTemplateLang::where('parent_id', $template->id)
                    ->where('lang', $langCode)
                    ->where('created_by', 1)
                    ->first();

                if ($existingContent) {
                    continue;
                }

                $translation = $templateData['translations'][$langCode] ?? $templateData['translations']['en'];

                NotificationTemplateLang::updateOrCreate([
                    'parent_id' => $template->id,
                    'lang' => $langCode,
                    'created_by' => 1
                ], [
                    'title' => $translation['title'],
                    'content' => $translation['content']
                ]);
            }
        }
    }
}
