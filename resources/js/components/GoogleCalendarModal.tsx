import { useState, useEffect } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { ChevronLeft, ChevronRight, Calendar as CalendarIcon, Clock, MapPin, User, FileText, RefreshCw } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { router } from '@inertiajs/react';
import Calendar from '@/pages/calendar';

interface GoogleCalendarModalProps {
  isOpen: boolean;
  onClose: () => void;
  caseId?: number;
  initialDate?: string;
}

interface CalendarEvent {
  id: string;
  summary: string;
  description?: string;
  start: string;
  end: string;
  type?: string;
  color?: string;
  case_title?: string;
  court_name?: string;
  judge_name?: string;
  status?: string;
}

export default function GoogleCalendarModal({ isOpen, onClose, caseId, initialDate }: GoogleCalendarModalProps) {
  const { t } = useTranslation();
  const [events, setEvents] = useState<CalendarEvent[]>([]);
  const [loading, setLoading] = useState(false);
  const [currentDate, setCurrentDate] = useState(new Date(initialDate || new Date()));
  const [viewType, setViewType] = useState<'month' | 'week' | 'day'>('month');
  const [selectedEvent, setSelectedEvent] = useState<CalendarEvent | null>(null);
  const [isEventModalOpen, setIsEventModalOpen] = useState(false);

  useEffect(() => {
    if (isOpen) {
      fetchGoogleCalendarEvents();
    }
  }, [isOpen, currentDate, viewType]);

  const fetchGoogleCalendarEvents = async () => {
    setLoading(true);
    try {
      const response = await fetch('/api/google-calendar/events?maxResults=50');
      const data = await response.json();
      if (data.success) {
        setEvents(data.events.map((event: any) => ({
          ...event,
          color: '#4285f4', // Google Calendar blue
          type: 'google_calendar'
        })));
      } else {
        console.error('Failed to fetch events:', data.message);
        if (data.message?.includes('not authorized')) {
          // Redirect to authorization
          window.location.href = '/google-calendar/auth';
        }
      }
    } catch (error) {
      console.error('Failed to fetch Google Calendar events:', error);
    } finally {
      setLoading(false);
    }
  };

  const syncCalendarEvents = async () => {
    setLoading(true);
    try {
      const response = await fetch('/api/google-calendar/sync', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        }
      });
      const data = await response.json();
      if (data.success) {
        setEvents(data.events.map((event: any) => ({
          ...event,
          color: '#4285f4',
          type: 'google_calendar'
        })));
      } else {
        console.error('Failed to sync events:', data.message);
      }
    } catch (error) {
      console.error('Failed to sync Google Calendar events:', error);
    } finally {
      setLoading(false);
    }
  };

  const navigateDate = (direction: 'prev' | 'next' | 'today') => {
    let newDate = new Date(currentDate);
    
    if (direction === 'today') {
      newDate = new Date();
    } else if (direction === 'prev') {
      if (viewType === 'month') {
        newDate.setMonth(newDate.getMonth() - 1);
      } else if (viewType === 'week') {
        newDate.setDate(newDate.getDate() - 7);
      } else {
        newDate.setDate(newDate.getDate() - 1);
      }
    } else {
      if (viewType === 'month') {
        newDate.setMonth(newDate.getMonth() + 1);
      } else if (viewType === 'week') {
        newDate.setDate(newDate.getDate() + 7);
      } else {
        newDate.setDate(newDate.getDate() + 1);
      }
    }

    setCurrentDate(newDate);
  };

  const generateCalendarDays = () => {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();
    
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const startDate = new Date(firstDay);
    startDate.setDate(startDate.getDate() - firstDay.getDay());
    
    const endDate = new Date(lastDay);
    endDate.setDate(endDate.getDate() + (6 - lastDay.getDay()));

    const days = [];
    const current = new Date(startDate);
    
    while (current <= endDate) {
      days.push(new Date(current));
      current.setDate(current.getDate() + 1);
    }
    
    return days;
  };

  const getEventsForDate = (date: Date) => {
    const dateStr = date.toISOString().split('T')[0];
    return events.filter((event) => {
      const eventDateStr = event.start.split('T')[0];
      return eventDateStr === dateStr;
    });
  };

  const handleEventClick = (event: CalendarEvent) => {
    setSelectedEvent(event);
    setIsEventModalOpen(true);
  };

  const formatTime = (dateTime: string) => {
    if (!dateTime) return '';
    try {
      const date = new Date(dateTime);
      return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    } catch (e) {
      return dateTime;
    }
  };

  const renderMonthView = () => {
    const days = generateCalendarDays();
    const weeks = [];
    
    for (let i = 0; i < days.length; i += 7) {
      weeks.push(days.slice(i, i + 7));
    }

    return (
      <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
        <div className="grid grid-cols-7 gap-0 border-b">
          {['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].map((day) => (
            <div key={day} className="p-3 text-center font-medium text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800">
              {t(day)}
            </div>
          ))}
        </div>
        
        {weeks.map((week, weekIndex) => (
          <div key={weekIndex} className="grid grid-cols-7 gap-0">
            {week.map((day, dayIndex) => {
              const dayEvents = getEventsForDate(day);
              const isCurrentMonth = day.getMonth() === currentDate.getMonth();
              const isToday = day.toDateString() === new Date().toDateString();
              
              return (
                <div 
                  key={dayIndex} 
                  className={`min-h-[120px] p-2 border-r border-b border-gray-200 dark:border-gray-700 ${
                    !isCurrentMonth ? 'bg-gray-50 dark:bg-gray-800' : 'bg-white dark:bg-gray-900'
                  }`}
                >
                  <div className={`text-sm font-medium mb-1 ${
                    isToday 
                      ? 'bg-blue-500 text-white rounded-full w-6 h-6 flex items-center justify-center' 
                      : isCurrentMonth 
                        ? 'text-gray-900 dark:text-gray-100' 
                        : 'text-gray-400 dark:text-gray-600'
                  }`}>
                    {day.getDate()}
                  </div>
                  
                  <div className="space-y-1">
                    {dayEvents.slice(0, 3).map((event) => (
                      <div
                        key={event.id}
                        onClick={() => handleEventClick(event)}
                        className="text-xs p-1 rounded cursor-pointer hover:opacity-80 truncate"
                        style={{ backgroundColor: `${event.color}20`, color: event.color }}
                      >
                        <div className="flex items-center gap-1">
                          <CalendarIcon className="h-3 w-3" />
                          <span className="truncate">{event.summary}</span>
                        </div>
                        <div className="text-xs opacity-75 truncate">
                          {formatTime(event.start)}
                        </div>
                      </div>
                    ))}
                    {dayEvents.length > 3 && (
                      <div className="text-xs text-gray-500 dark:text-gray-400 cursor-pointer hover:underline">
                        +{dayEvents.length - 3} more
                      </div>
                    )}
                  </div>
                </div>
              );
            })}
          </div>
        ))}
      </div>
    );
  };

  const renderWeekView = () => {
    const startOfWeek = new Date(currentDate);
    startOfWeek.setDate(currentDate.getDate() - currentDate.getDay());
    
    const days = [];
    for (let i = 0; i < 7; i++) {
      const day = new Date(startOfWeek);
      day.setDate(startOfWeek.getDate() + i);
      days.push(day);
    }

    const hours = Array.from({ length: 24 }, (_, i) => i);

    return (
      <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
        <div className="grid grid-cols-8 gap-0 border-b">
          <div className="p-3 text-center font-medium text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800">
            {t('Time')}
          </div>
          {days.map((day, index) => {
            const isToday = day.toDateString() === new Date().toDateString();
            return (
              <div key={index} className={`p-3 text-center font-medium bg-gray-50 dark:bg-gray-800 ${
                isToday ? 'text-blue-600 dark:text-blue-400' : 'text-gray-500 dark:text-gray-400'
              }`}>
                <div className="text-xs">{day.toLocaleDateString(undefined, { weekday: 'short' })}</div>
                <div className={`text-lg ${isToday ? 'bg-blue-500 text-white rounded-full w-8 h-8 flex items-center justify-center mx-auto' : ''}`}>
                  {day.getDate()}
                </div>
              </div>
            );
          })}
        </div>
        
        <div className="max-h-[400px] overflow-y-auto">
          {hours.map((hour) => (
            <div key={hour} className="grid grid-cols-8 gap-0 border-b border-gray-100 dark:border-gray-700">
              <div className="p-2 text-xs text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 border-r">
                {hour.toString().padStart(2, '0')}:00
              </div>
              {days.map((day, dayIndex) => {
                const dayEvents = getEventsForDate(day).filter(event => {
                  if (!event.start) return hour === 0;
                  try {
                    const eventHour = new Date(event.start).getHours();
                    return eventHour === hour;
                  } catch (e) {
                    return hour === 0;
                  }
                });
                
                return (
                  <div key={dayIndex} className="min-h-[60px] p-1 border-r border-gray-100 dark:border-gray-700 relative">
                    {dayEvents.map((event) => (
                      <div
                        key={event.id}
                        onClick={() => handleEventClick(event)}
                        className="text-xs p-1 rounded cursor-pointer hover:opacity-80 mb-1"
                        style={{ backgroundColor: `${event.color}20`, color: event.color }}
                      >
                        <div className="flex items-center gap-1">
                          <CalendarIcon className="h-3 w-3" />
                          <span className="truncate font-medium">{event.summary}</span>
                        </div>
                        <div className="text-xs opacity-75">{formatTime(event.start)}</div>
                      </div>
                    ))}
                  </div>
                );
              })}
            </div>
          ))}
        </div>
      </div>
    );
  };

  const renderDayView = () => {
    const dayEvents = getEventsForDate(currentDate);
    const hours = Array.from({ length: 24 }, (_, i) => i);
    const isToday = currentDate.toDateString() === new Date().toDateString();

    return (
      <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
        <div className="p-4 border-b bg-gray-50 dark:bg-gray-800">
          <div className="text-center">
            <div className="text-sm text-gray-500 dark:text-gray-400">
              {currentDate.toLocaleDateString(undefined, { weekday: 'long' })}
            </div>
            <div className={`text-2xl font-semibold ${
              isToday ? 'text-blue-600 dark:text-blue-400' : 'text-gray-900 dark:text-gray-100'
            }`}>
              {currentDate.getDate()}
            </div>
            <div className="text-sm text-gray-500 dark:text-gray-400">
              {currentDate.toLocaleDateString(undefined, { month: 'long', year: 'numeric' })}
            </div>
          </div>
        </div>
        
        <div className="max-h-[400px] overflow-y-auto">
          {hours.map((hour) => {
            const hourEvents = dayEvents.filter(event => {
              if (!event.start) return hour === 0;
              try {
                const eventHour = new Date(event.start).getHours();
                return eventHour === hour;
              } catch (e) {
                return hour === 0;
              }
            });
            
            return (
              <div key={hour} className="flex border-b border-gray-100 dark:border-gray-700">
                <div className="w-20 p-3 text-sm text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 border-r">
                  {hour.toString().padStart(2, '0')}:00
                </div>
                <div className="flex-1 min-h-[80px] p-2">
                  {hourEvents.map((event) => (
                    <div
                      key={event.id}
                      onClick={() => handleEventClick(event)}
                      className="p-3 mb-2 rounded-lg cursor-pointer hover:opacity-80 border-l-4"
                      style={{ 
                        backgroundColor: `${event.color}10`, 
                        borderLeftColor: event.color 
                      }}
                    >
                      <div className="flex items-center justify-between mb-2">
                        <div className="flex items-center gap-2">
                          <CalendarIcon className="h-4 w-4" />
                          <span className="font-medium" style={{ color: event.color }}>
                            {event.summary}
                          </span>
                        </div>
                        <Badge variant="outline" className="text-xs">
                          {t('Google Calendar')}
                        </Badge>
                      </div>
                      <div className="text-sm text-gray-600 dark:text-gray-300 mb-1">
                        {formatTime(event.start)} - {formatTime(event.end)}
                      </div>
                      {event.description && (
                        <div className="text-sm text-gray-500 dark:text-gray-400">
                          {event.description}
                        </div>
                      )}
                    </div>
                  ))}
                </div>
              </div>
            );
          })}
        </div>
      </div>
    );
  };

  const renderEventModal = () => (
    <Dialog open={isEventModalOpen} onOpenChange={setIsEventModalOpen}>
      <DialogContent className="max-w-md">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <CalendarIcon className="h-5 w-5" />
            {selectedEvent?.summary}
          </DialogTitle>
        </DialogHeader>
        
        {selectedEvent && (
          <div className="space-y-4">
            <div className="flex items-center gap-2 text-sm">
              <CalendarIcon className="h-4 w-4 text-gray-500" />
              <span>{new Date(selectedEvent.start).toLocaleDateString()}</span>
              <Clock className="h-4 w-4 text-gray-500 ml-2" />
              <span>{formatTime(selectedEvent.start)} - {formatTime(selectedEvent.end)}</span>
            </div>

            {selectedEvent.description && (
              <div>
                <h4 className="font-medium text-sm mb-1">{t('Description')}</h4>
                <p className="text-sm text-gray-600 dark:text-gray-300">
                  {selectedEvent.description}
                </p>
              </div>
            )}

            <div className="flex items-center gap-2">
              <Badge style={{ backgroundColor: `${selectedEvent.color}20`, color: selectedEvent.color }}>
                {t('Google Calendar')}
              </Badge>
            </div>
          </div>
        )}
      </DialogContent>
    </Dialog>
  );

  return (
    <>
      <Dialog open={isOpen} onOpenChange={onClose}>
        <DialogContent className="max-w-6xl max-h-[90vh] overflow-hidden">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2">
              <CalendarIcon className="h-5 w-5" />
              {t('Google Calendar View')}
            </DialogTitle>
          </DialogHeader>
          
          <div className="space-y-4">
            {/* Calendar Controls */}
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-4">
                <div className="flex items-center gap-2">
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => navigateDate('prev')}
                  >
                    <ChevronLeft className="h-4 w-4" />
                  </Button>
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => navigateDate('next')}
                  >
                    <ChevronRight className="h-4 w-4" />
                  </Button>
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => navigateDate('today')}
                  >
                    {t('Today')}
                  </Button>
                </div>
                
                <h2 className="text-xl font-semibold">
                  {currentDate.toLocaleDateString(undefined, { 
                    month: 'long', 
                    year: 'numeric' 
                  })}
                </h2>
              </div>

              <div className="flex items-center gap-2">
                <Button
                  variant="outline"
                  size="sm"
                  onClick={syncCalendarEvents}
                  disabled={loading}
                >
                  <RefreshCw className={`h-4 w-4 mr-2 ${loading ? 'animate-spin' : ''}`} />
                  {t('Sync')}
                </Button>
                
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => window.open('/google-calendar/auth', '_blank')}
                >
                  <Calendar className="h-4 w-4 mr-2" />
                  {t('Authorize')}
                </Button>
                
                <Button
                  variant={viewType === 'month' ? 'default' : 'outline'}
                  size="sm"
                  onClick={() => setViewType('month')}
                >
                  {t('Month')}
                </Button>
                <Button
                  variant={viewType === 'week' ? 'default' : 'outline'}
                  size="sm"
                  onClick={() => setViewType('week')}
                >
                  {t('Week')}
                </Button>
                <Button
                  variant={viewType === 'day' ? 'default' : 'outline'}
                  size="sm"
                  onClick={() => setViewType('day')}
                >
                  {t('Day')}
                </Button>
              </div>
            </div>

            {/* Calendar View */}
            <div className="overflow-auto max-h-[60vh]">
              {loading ? (
                <div className="flex items-center justify-center h-64">
                  <RefreshCw className="h-8 w-8 animate-spin text-gray-400" />
                  <span className="ml-2 text-gray-500">{t('Loading calendar events...')}</span>
                </div>
              ) : (
                <>
                  {viewType === 'month' && renderMonthView()}
                  {viewType === 'week' && renderWeekView()}
                  {viewType === 'day' && renderDayView()}
                </>
              )}
            </div>

            {/* Event Summary */}
            <Card>
              <CardHeader>
                <CardTitle className="text-sm">{t('Events Summary')}</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="text-sm text-gray-600 dark:text-gray-300">
                  {t('Total Events')}: {events.length}
                </div>
              </CardContent>
            </Card>
          </div>
        </DialogContent>
      </Dialog>

      {renderEventModal()}
    </>
  );
}