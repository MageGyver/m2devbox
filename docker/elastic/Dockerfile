ARG ELASTIC_VERSION=7.6.2
FROM docker.elastic.co/elasticsearch/elasticsearch:${ELASTIC_VERSION}

RUN /usr/share/elasticsearch/bin/elasticsearch-plugin install analysis-phonetic
RUN /usr/share/elasticsearch/bin/elasticsearch-plugin install analysis-icu
