{!! '<?xml version="1.0" encoding="UTF-8"?>'."\n" !!}
<rss version="2.0" xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" xmlns:atom="http://www.w3.org/2005/Atom">
  <channel>
    <title>{{ $channel['title'] }}</title>
    <link>{{ $siteUrl }}</link>
    <language>{{ $channel['language'] }}</language>
    <description>{{ $channel['description'] }}</description>
    <itunes:author>{{ $channel['author'] }}</itunes:author>
    <itunes:summary>{{ $channel['description'] }}</itunes:summary>
    <itunes:explicit>{{ $channel['explicit'] ? 'yes' : 'no' }}</itunes:explicit>
    <itunes:category text="{{ $channel['category'] }}"/>
    @if(!empty($channel['email']))
    <itunes:owner>
      <itunes:name>{{ $channel['author'] }}</itunes:name>
      <itunes:email>{{ $channel['email'] }}</itunes:email>
    </itunes:owner>
    @endif
    <atom:link href="{{ $feedUrl }}" rel="self" type="application/rss+xml"/>
    @foreach($items as $item)
    <item>
      <title>{{ $item['title'] }}</title>
      <guid isPermaLink="false">{{ $item['guid'] }}</guid>
      <pubDate>{{ $item['pubDate'] }}</pubDate>
      <description>{{ $item['description'] }}</description>
      <itunes:author>{{ $item['description'] }}</itunes:author>
      <itunes:duration>{{ $item['duration'] }}</itunes:duration>
      @if($item['coverUrl'])
      <itunes:image href="{{ $item['coverUrl'] }}"/>
      @endif
      <enclosure url="{{ $item['audioUrl'] }}" length="{{ $item['audioBytes'] }}" type="audio/mpeg"/>
    </item>
    @endforeach
  </channel>
</rss>
